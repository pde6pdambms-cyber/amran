<?php
/**
 * Class EmployeeModel
 * Model untuk mengelola data karyawan PDAM menggunakan PDO.
 * Berisi operasi: Create (Insert), Read (Select), Update, dan Delete.
 */
class EmployeeModel {
    private $db;

    public function __construct($pdoConnection) {
        $this->db = $pdoConnection;
    }

    /**
     * CREATE: Menambahkan data karyawan baru
     * 
     * @param string $noPegawai
     * @param string $nama
     * @param string $jabatan
     * @param string $departemen
     * @return bool
     */
    public function insertEmployee($noPegawai, $nama, $jabatan, $departemen) {
        $sql = "INSERT INTO karyawan_pdam (no_pegawai, nama, jabatan, departemen, created_at) 
                VALUES (:no_pegawai, :nama, :jabatan, :departemen, NOW())";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':no_pegawai' => $noPegawai,
                ':nama' => $nama,
                ':jabatan' => $jabatan,
                ':departemen' => $departemen
            ]);
        } catch (PDOException $e) {
            // Tangani error atau log sesuai kebutuhan
            return false;
        }
    }

    /**
     * READ: Mengambil semua data karyawan atau berdasarkan ID
     * 
     * @param int|null $id
     * @return array
     */
    public function getEmployees($id = null) {
        if ($id !== null) {
            $sql = "SELECT * FROM karyawan_pdam WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $sql = "SELECT * FROM karyawan_pdam ORDER BY created_at DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * UPDATE: Memperbarui data karyawan berdasarkan ID
     * 
     * @param int $id
     * @param string $noPegawai
     * @param string $nama
     * @param string $jabatan
     * @param string $departemen
     * @return bool
     */
    public function updateEmployee($id, $noPegawai, $nama, $jabatan, $departemen) {
        $sql = "UPDATE karyawan_pdam 
                SET no_pegawai = :no_pegawai, 
                    nama = :nama, 
                    jabatan = :jabatan, 
                    departemen = :departemen, 
                    updated_at = NOW() 
                WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':no_pegawai' => $noPegawai,
                ':nama' => $nama,
                ':jabatan' => $jabatan,
                ':departemen' => $departemen
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * DELETE: Menghapus data karyawan berdasarkan ID
     * 
     * @param int $id
     * @return bool
     */
    public function deleteEmployee($id) {
        $sql = "DELETE FROM karyawan_pdam WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>