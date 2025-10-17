<?php
class QRCode {
    private $db;
    private $table_name = 'qr_sessions';
    private $history_table = 'qr_code_history';
    private $qr_api = 'https://api.qrserver.com/v1/create-qr-code/';
    private $qr_size = '300x300';
    private $session_duration = 300; // 5 minutes in seconds

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Generate a unique QR code for a student
     * @param string $student_id Student ID (e.g., COL-12345)
     * @return array QR code data with session info
     */
    public function generateQRCode(string $student_id): array {
        try {
            // Create unique session ID
            $session_id = 'QR_' . $student_id . '_' . time() . '_' . bin2hex(random_bytes(8));
            
            // Create QR data with timestamp and hash
            $timestamp = time();
            $qr_data = [
                'student_id' => $student_id,
                'session_id' => $session_id,
                'timestamp' => $timestamp,
                'type' => 'attendance'
            ];
            
            // Generate hash for verification
            $qr_hash = hash('sha256', json_encode($qr_data) . 'JAVERIANS_QR_SECRET_' . $timestamp);
            $qr_data['hash'] = $qr_hash;
            
            $qr_json = json_encode($qr_data);
            $expires_at = date('Y-m-d H:i:s', $timestamp + $this->session_duration);
            
            // Store in database
            $query = "INSERT INTO {$this->table_name} (session_id, student_id, qr_data, qr_hash, expires_at, ip_address) 
                      VALUES (:session_id, :student_id, :qr_data, :qr_hash, :expires_at, :ip_address)";
            
            $stmt = $this->db->prepare($query);
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            $stmt->bindValue(':session_id', $session_id);
            $stmt->bindValue(':student_id', $student_id);
            $stmt->bindValue(':qr_data', $qr_json);
            $stmt->bindValue(':qr_hash', $qr_hash);
            $stmt->bindValue(':expires_at', $expires_at);
            $stmt->bindValue(':ip_address', $ip_address);
            
            if (!$stmt->execute()) {
                return ['success' => false, 'message' => 'Failed to create QR session'];
            }
            
            // Generate QR code image URL
            $qr_url = $this->qr_api . '?size=' . $this->qr_size . '&data=' . urlencode($qr_json);
            
            // Store in history
            $this->storeQRHistory($student_id, $qr_json, $expires_at);
            
            return [
                'success' => true,
                'session_id' => $session_id,
                'student_id' => $student_id,
                'qr_url' => $qr_url,
                'qr_data' => $qr_json,
                'expires_at' => $expires_at,
                'expires_in_seconds' => $this->session_duration
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error generating QR code: ' . $e->getMessage()];
        }
    }

    /**
     * Verify and validate a scanned QR code
     * @param string $qr_data QR data from scanner
     * @return array Verification result
     */
    public function verifyQRCode(string $qr_data): array {
        try {
            $decoded = json_decode($qr_data, true);
            
            if (!is_array($decoded) || !isset($decoded['session_id'], $decoded['student_id'], $decoded['hash'])) {
                return ['success' => false, 'message' => 'Invalid QR format'];
            }
            
            $session_id = $decoded['session_id'];
            $student_id = $decoded['student_id'];
            $provided_hash = $decoded['hash'];
            
            // Verify hash
            $data_for_hash = $decoded;
            unset($data_for_hash['hash']);
            $expected_hash = hash('sha256', json_encode($data_for_hash) . 'JAVERIANS_QR_SECRET_' . $decoded['timestamp']);
            
            if ($provided_hash !== $expected_hash) {
                return ['success' => false, 'message' => 'QR code verification failed - invalid hash'];
            }
            
            // Check if session exists and is valid
            $query = "SELECT * FROM {$this->table_name} WHERE session_id = :session_id AND student_id = :student_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':session_id', $session_id);
            $stmt->bindValue(':student_id', $student_id);
            $stmt->execute();
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$session) {
                return ['success' => false, 'message' => 'QR session not found'];
            }
            
            // Check if expired
            if (strtotime($session['expires_at']) < time()) {
                return ['success' => false, 'message' => 'QR code has expired'];
            }
            
            // Check if already used
            if ($session['is_used']) {
                return ['success' => false, 'message' => 'QR code has already been used'];
            }
            
            return [
                'success' => true,
                'session_id' => $session_id,
                'student_id' => $student_id,
                'message' => 'QR code verified successfully'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error verifying QR code: ' . $e->getMessage()];
        }
    }

    /**
     * Mark QR session as used
     * @param string $session_id Session ID
     * @return bool Success status
     */
    public function markQRAsUsed(string $session_id): bool {
        try {
            $query = "UPDATE {$this->table_name} SET is_used = 1, used_at = NOW() WHERE session_id = :session_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':session_id', $session_id);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get active QR sessions for a student
     * @param string $student_id Student ID
     * @return array Active sessions
     */
    public function getActiveSessions(string $student_id): array {
        try {
            $query = "SELECT * FROM {$this->table_name} 
                      WHERE student_id = :student_id AND expires_at > NOW() AND is_used = 0
                      ORDER BY created_at DESC LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $student_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Store QR code in history for analytics
     * @param string $student_id Student ID
     * @param string $qr_data QR data
     * @param string $expires_at Expiration time
     * @return bool Success status
     */
    private function storeQRHistory(string $student_id, string $qr_data, string $expires_at): bool {
        try {
            $query = "INSERT INTO {$this->history_table} (student_id, qr_code_data, expires_at) 
                      VALUES (:student_id, :qr_data, :expires_at)";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $student_id);
            $stmt->bindValue(':qr_data', $qr_data);
            $stmt->bindValue(':expires_at', $expires_at);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Clean up expired QR sessions
     * @return int Number of deleted sessions
     */
    public function cleanupExpiredSessions(): int {
        try {
            $query = "DELETE FROM {$this->table_name} WHERE expires_at < NOW()";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get QR code statistics for a student
     * @param string $student_id Student ID
     * @return array Statistics
     */
    public function getQRStatistics(string $student_id): array {
        try {
            $query = "SELECT 
                        COUNT(*) as total_generated,
                        SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) as total_used,
                        MAX(created_at) as last_generated
                      FROM {$this->table_name}
                      WHERE student_id = :student_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $student_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
