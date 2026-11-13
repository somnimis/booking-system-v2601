<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EquipmentImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
 public function run()
    {
        DB::table('equipment_images')->insert([
            [
                'equipment_id' => 1,
                'image_url' => 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1761155622/i6gbehp85yeu9liznurb.webp',
                'description' => 'Primary image for Sound System (Basic)',
                'sort_order' => 1,
                'image_type' => 'Primary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_id' => 2,
                'image_url' => 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1761155611/efralq2l8azz8r492zzi.jpg',
                'description' => 'Primary image for Sound System (Large)',
                'sort_order' => 1,
                'image_type' => 'Primary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_id' => 3,
                'image_url' => 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1761155608/khmlh7sytkdwrtjjl6qj.jpg',
                'description' => 'Primary image for Additional Speakers',
                'sort_order' => 1,
                'image_type' => 'Primary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_id' => 4,
                'image_url' => 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1761155609/l7pimudnoba8x9dn0rhe.jpg',
                'description' => 'Primary image for Additional Mics',
                'sort_order' => 1,
                'image_type' => 'Primary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
                        [
                'equipment_id' => 5,
                'image_url' => 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1761155625/fk3larw1t0xnul5wwpjf.webp',
                'description' => 'Primary image for Lights (RGB Parled with Dimmer)',
                'sort_order' => 1,
                'image_type' => 'Primary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
                                    [
                'equipment_id' => 6,
                'image_url' => 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1761155620/in8k2u5eqdqbtwdgixbd.webp',
                'description' => 'Primary image for Moving Heads (with Controller)',
                'sort_order' => 1,
                'image_type' => 'Primary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
[
                'equipment_id' => 7,
                'image_url' => 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1761155604/qe72ovxfeh4c3no2pxt2.jpg',
                'description' => 'Primary image for Smoke machine',
                'sort_order' => 1,
                'image_type' => 'Primary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'equipment_id' => 8,
                'image_url' => 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1761155642/yvhmtgva3jpqcfxbhbmt.jpg',
                'description' => 'Primary image for Follow spot',
                'sort_order' => 1,
                'image_type' => 'Primary',
                'created_at' => now(),
                'updated_at' => now(),
            ]
            ]);
        }
    }

