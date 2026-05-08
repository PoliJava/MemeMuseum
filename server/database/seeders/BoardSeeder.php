<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Board;

class BoardSeeder extends Seeder
{
    public function run(): void
    {
        $boards = [
            ['slug'=>'cls',  'name'=>'Classics',         'description'=>'Pre-2012 artifacts and foundational works.'],
            ['slug'=>'rx',   'name'=>'Reaction',          'description'=>'Expressions, faces, and the full spectrum of emotion.'],
            ['slug'=>'dank', 'name'=>'Dank',              'description'=>'Contemporary works of ironic significance.'],
            ['slug'=>'oc',   'name'=>'Original Content',  'description'=>'New acquisitions. Must be original.'],
            ['slug'=>'meta', 'name'=>'Meta',              'description'=>'Discussion of the museum itself.'],
            ['slug'=>'arc',  'name'=>'Archive',           'description'=>'Preserved specimens. Read-only.', 'is_archived'=>true, 'is_readonly'=>true],
        ];

        foreach ($boards as $board) {
            Board::updateOrCreate(
                ['slug' => $board['slug']],
                array_merge($board, [
                    'is_archived' => $board['is_archived'] ?? false,
                    'is_readonly' => $board['is_readonly'] ?? false
                ])
            );
        }
    }
}