<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuthTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('roles');

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'role_id'       => ['type' => 'INT', 'unsigned' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 190],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('role_id');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('users');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true],
            'token_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'api'],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('personal_access_tokens');

        $now = date('Y-m-d H:i:s');

        $this->db->table('roles')->insertBatch([
            ['id' => 1, 'name' => 'admin', 'label' => 'Admin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'member', 'label' => 'Nhan vien', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->db->table('users')->insertBatch([
            [
                'role_id'       => 1,
                'name'          => 'Admin',
                'email'         => 'admin@example.com',
                'password_hash' => password_hash('Admin@123', PASSWORD_DEFAULT),
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'role_id'       => 2,
                'name'          => 'Nhan vien',
                'email'         => 'staff@example.com',
                'password_hash' => password_hash('Staff@123', PASSWORD_DEFAULT),
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('personal_access_tokens', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('roles', true);
    }
}

