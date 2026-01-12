<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePulseTables extends Migration
{
    public function up()
    {
        // Table: pulse_values
        $this->forge->addField([
            'id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'timestamp' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'type'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'key'       => ['type' => 'TEXT'],
            'key_hash'  => ['type' => 'CHAR', 'constraint' => 32], // MD5 hex
            'value'     => ['type' => 'TEXT'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('timestamp');
        $this->forge->addKey('type');
        $this->forge->addUniqueKey(['type', 'key_hash']);
        $this->forge->createTable('pulse_values');

        // Table: pulse_entries
        $this->forge->addField([
            'id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'timestamp' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'type'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'key'       => ['type' => 'TEXT'],
            'key_hash'  => ['type' => 'CHAR', 'constraint' => 32],
            'value'     => ['type' => 'BIGINT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('timestamp');
        $this->forge->addKey('type');
        $this->forge->addKey('key_hash');
        // Composite index for aggregation speed
        $this->forge->addKey(['timestamp', 'type', 'key_hash', 'value']);
        $this->forge->createTable('pulse_entries');

        // Table: pulse_aggregates
        $this->forge->addField([
            'id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'bucket'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'period'    => ['type' => 'INT', 'constraint' => 8, 'unsigned' => true],
            'type'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'key'       => ['type' => 'TEXT'],
            'key_hash'  => ['type' => 'CHAR', 'constraint' => 32],
            'aggregate' => ['type' => 'VARCHAR', 'constraint' => 255],
            'value'     => ['type' => 'DECIMAL', 'constraint' => '20,2'],
            'count'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['bucket', 'period', 'type', 'aggregate', 'key_hash']);
        $this->forge->addKey(['period', 'bucket']);
        $this->forge->addKey('type');
        $this->forge->addKey(['period', 'type', 'aggregate', 'bucket']);
        $this->forge->createTable('pulse_aggregates');
    }

    public function down()
    {
        $this->forge->dropTable('pulse_values');
        $this->forge->dropTable('pulse_entries');
        $this->forge->dropTable('pulse_aggregates');
    }
}
