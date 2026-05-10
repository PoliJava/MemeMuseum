<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Board;

class BoardSeeder extends Seeder
{
    public function run(): void
    {
        $boards = [
            [
                'slug'        => 'cls',
                'name'        => 'Classics',
                'description' => 'Pre-2013 artefacts and foundational works. Rage comics, early advice animals, image macros.',
                'sticky_body' => "Welcome to /cls/ — the Classical wing.\nThis board is reserved for memes with documented cultural significance predating 2013.\n>Rage comics, early advice animals, and image macros belong here.\n>No modern formats or post-2012 content.\n>Low-effort reposts of well-known specimens are discouraged — add context.",
            ],
            [
                'slug'        => 'bate',
                'name'        => 'Debate',
                'description' => 'Critical discussion and historical analysis. Argue formats, trace lineages, debate the cultural impact of the works.',
                'sticky_body' => "Welcome to /bate/ — the Debate hall.\nThis board is for critical discussion and historical analysis of meme culture.\n>Argue formats, trace lineages, contest attributions.\n>Back your claims with examples where possible.\n>Bad-faith posts and flamewars will be removed.",
            ],
            [
                'slug'        => 'dank',
                'name'        => 'Dank',
                'description' => 'Post-ironic, deep-fried, absurdist, and otherwise unclassifiable surrealist works.',
                'sticky_body' => "Welcome to /dank/ — the Surrealist wing.\nPost-ironic, deep-fried, absurdist, and otherwise unclassifiable works live here.\n>If it belongs nowhere else, it belongs here.\n>Quality over quantity — do not flood the board.\n>No reposts within 30 days.",
            ],
            [
                'slug'        => 'oc',
                'name'        => 'Original Content',
                'description' => 'New acquisitions. You can make dated memes here: inspiration from all eras is welcome.',
                'sticky_body' => "Welcome to /oc/ — New Acquisitions.\nThis board accepts original works only. Do not post content you did not create.\n>Classic formats and templates encouraged — make something new with the old tools.\n>Watermark or sign your work if you want attribution.\n>Verified OC may be considered for transfer to a permanent collection board.",
            ],
            [
                'slug'        => 'mdn',
                'name'        => 'Modern',
                'description' => 'Post-2012 nostalgia and contemporary memes. Current memes allowed.',
                'sticky_body' => "Welcome to /mdn/ — the Modern wing.\nThis board is for contemporary memes and posts from 2012 onwards.\n>Keep it fresh — no stale content.\n>Quality over quantity — do not flood the board.\n>No reposts within 30 days!",
            ],
            [
                'slug'        => 'meta',
                'name'        => 'Meta',
                'description' => 'Discussion of the museum itself — board organisation, rules, feedback, and bug reports.',
                'sticky_body' => "Welcome to /meta/ — Curatorial Discussion.\nUse this board to discuss the museum: board organisation, rules, missing exhibits, and site feedback.\n>Be constructive. Complaints without suggestions are noted but not prioritised.\n>Bug reports and feature requests are welcome.\n>Do not use /meta/ to post memes — use the appropriate collection board.",
            ],
        ];

        foreach ($boards as $board) {
            Board::updateOrCreate(
                ['slug' => $board['slug']],
                array_merge($board, [
                    'is_archived' => $board['is_archived'] ?? false,
                    'is_readonly' => $board['is_readonly'] ?? false,
                ])
            );
        }
    }
}
