<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EquipmentItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('equipment_items')->insert([
            // --- SOUND SYSTEMS ---
            // Equipment ID 1: Sound System (Basic with 2 mics, Player) - 7 items
            [
                'equipment_id' => 1,
                'item_name' => 'Basic Sound System Unit #001',
                'condition_id' => 2, // Good
                'status_id' => 1, // Available
                'barcode_number' => 'SOUND001',
                'item_notes' => 'Complete basic sound system with 2 mics and player',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 1,
                'item_name' => 'Basic Sound System Unit #002',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'SOUND002',
                'item_notes' => 'Backup basic sound system, fully tested',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 1,
                'item_name' => 'Basic Sound System Unit #003',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'SOUND003',
                'item_notes' => 'Recently purchased, latest model',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 1,
                'item_name' => 'Basic Sound System Unit #004',
                'status_id' => 3, // In Use
                'condition_id' => 3, // Fair
                'barcode_number' => 'SOUND004',
                'item_notes' => 'Currently deployed at main hall, shows some wear',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 1,
                'item_name' => 'Basic Sound System Unit #005',
                'status_id' => 1,
                'condition_id' => 3, // Fair
                'barcode_number' => 'SOUND005',
                'item_notes' => 'Minor cosmetic scratches, fully functional',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 1,
                'item_name' => 'Basic Sound System Unit #006',
                'status_id' => 4, // Maintenance
                'condition_id' => 4, // Needs Maintenance
                'barcode_number' => 'SOUND006',
                'item_notes' => 'Scheduled for speaker cone replacement',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 1,
                'item_name' => 'Basic Sound System Unit #007',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'SOUND007',
                'item_notes' => 'Recently serviced, ready for use',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 2: Sound System (Large with Sub, Digital Mixer, Processors) - 5 items
            [
                'equipment_id' => 2,
                'item_name' => 'Large Sound System Unit #001',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'LSOUND001',
                'item_notes' => 'Professional large sound system with subwoofer and digital mixer',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 2,
                'item_name' => 'Large Sound System Unit #002',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'LSOUND002',
                'item_notes' => 'Flagship system with advanced processing',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 2,
                'item_name' => 'Large Sound System Unit #003',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'LSOUND003',
                'item_notes' => 'Currently set up in auditorium for conference',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 2,
                'item_name' => 'Large Sound System Unit #004',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'LSOUND004',
                'item_notes' => 'Backup large system, well maintained',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 2,
                'item_name' => 'Large Sound System Unit #005',
                'status_id' => 4, // Maintenance
                'condition_id' => 4, // Needs Maintenance
                'barcode_number' => 'LSOUND005',
                'item_notes' => 'Amplifier check required, minor hum issue',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 3: Additional Speakers - 6 items
            [
                'equipment_id' => 3,
                'item_name' => 'Additional Speaker Pair A',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'SPKR001',
                'item_notes' => 'Pair of additional speakers, 500W each',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 3,
                'item_name' => 'Additional Speaker Pair B',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'SPKR002',
                'item_notes' => 'Backup speaker pair, currently deployed',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 3,
                'item_name' => 'Additional Speaker Pair C',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'SPKR003',
                'item_notes' => 'High-performance speakers, 800W each',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 3,
                'item_name' => 'Additional Speaker Pair D',
                'status_id' => 1,
                'condition_id' => 3, // Fair
                'barcode_number' => 'SPKR004',
                'item_notes' => 'Minor grill damage, but sounds perfect',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 3,
                'item_name' => 'Additional Speaker Pair E',
                'status_id' => 4, // Maintenance
                'condition_id' => 4, // Needs Maintenance
                'barcode_number' => 'SPKR005',
                'item_notes' => 'One speaker has blown tweeter',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 3,
                'item_name' => 'Additional Speaker Pair F',
                'status_id' => 5, // Damaged
                'condition_id' => 5, // Damaged
                'barcode_number' => 'SPKR006',
                'item_notes' => 'Cabinet damaged in transport, awaiting repair assessment',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 4: Additional Mics - 7 items
            [
                'equipment_id' => 4,
                'item_name' => 'Microphone Set A (4 units)',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'MICS001',
                'item_notes' => 'Set of 4 additional microphones',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 4,
                'item_name' => 'Microphone Set B (4 units)',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'MICS002',
                'item_notes' => 'Premium condenser mic set',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 4,
                'item_name' => 'Microphone Set C (4 units)',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'MICS003',
                'item_notes' => 'Currently at recording studio',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 4,
                'item_name' => 'Microphone Set D (4 units)',
                'status_id' => 2, // Reserved
                'condition_id' => 3, // Fair
                'barcode_number' => 'MICS004',
                'item_notes' => 'Reserved for weekend workshop, some wear on grills',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 4,
                'item_name' => 'Microphone Set E (2 units)',
                'status_id' => 1,
                'condition_id' => 3, // Fair
                'barcode_number' => 'MICS005',
                'item_notes' => 'Older model, still sounds good',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 4,
                'item_name' => 'Microphone Set F (4 units)',
                'status_id' => 4, // Maintenance
                'condition_id' => 4, // Needs Maintenance
                'barcode_number' => 'MICS006',
                'item_notes' => 'One mic needs new XLR connector',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 4,
                'item_name' => 'Microphone Set G (2 units)',
                'status_id' => 5, // Damaged
                'condition_id' => 5, // Damaged
                'barcode_number' => 'MICS007',
                'item_notes' => 'One mic has cracked casing, not usable',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // --- LIGHTS & EFFECTS ---
            // Equipment ID 5: Lights (RGB Parled with Dimmer) - 5 items
            [
                'equipment_id' => 5,
                'item_name' => 'RGB Parled Light Set A (6 units)',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'RGB001',
                'item_notes' => 'Set of 6 RGB Parled lights with dimmer controller',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 5,
                'item_name' => 'RGB Parled Light Set B (6 units)',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'RGB002',
                'item_notes' => 'Latest model with wireless control',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 5,
                'item_name' => 'RGB Parled Light Set C (4 units)',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'RGB003',
                'item_notes' => 'Installed in main stage setup',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 5,
                'item_name' => 'RGB Parled Light Set D (6 units)',
                'status_id' => 4, // Maintenance
                'condition_id' => 4, // Needs Maintenance
                'barcode_number' => 'RGB004',
                'item_notes' => 'Two lights have flickering issues',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 5,
                'item_name' => 'RGB Parled Light Set E (4 units)',
                'status_id' => 1,
                'condition_id' => 3, // Fair
                'barcode_number' => 'RGB005',
                'item_notes' => 'Older model, basic functionality only',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 6: Moving Heads (with Controller) - 4 items
            [
                'equipment_id' => 6,
                'item_name' => 'Moving Head Light Pair A',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'MOVE001',
                'item_notes' => 'Pair of moving head lights with DMX controller',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 6,
                'item_name' => 'Moving Head Light Pair B',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'MOVE002',
                'item_notes' => 'Recently serviced, smooth operation',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 6,
                'item_name' => 'Moving Head Light Pair C',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'MOVE003',
                'item_notes' => 'Currently at concert hall',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 6,
                'item_name' => 'Moving Head Light Pair D',
                'status_id' => 4, // Maintenance
                'condition_id' => 4, // Needs Maintenance
                'barcode_number' => 'MOVE004',
                'item_notes' => 'One unit has motor noise, needs lubrication',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 7: Smoke Machine - 4 items
            [
                'equipment_id' => 7,
                'item_name' => 'Smoke Machine Unit #001',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'SMOKE001',
                'item_notes' => 'Professional smoke machine with remote',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 7,
                'item_name' => 'Smoke Machine Unit #002',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'SMOKE002',
                'item_notes' => 'High-output haze machine',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 7,
                'item_name' => 'Smoke Machine Unit #003',
                'status_id' => 3, // In Use
                'condition_id' => 3, // Fair
                'barcode_number' => 'SMOKE003',
                'item_notes' => 'Works well but remote is finicky',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 7,
                'item_name' => 'Smoke Machine Unit #004',
                'status_id' => 4, // Maintenance
                'condition_id' => 4, // Needs Maintenance
                'barcode_number' => 'SMOKE004',
                'item_notes' => 'Heating element needs replacement',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 8: Follow Spot - 3 items
            [
                'equipment_id' => 8,
                'item_name' => 'Follow Spot Light #001',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'SPOT001',
                'item_notes' => '1000W follow spot light with stand',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 8,
                'item_name' => 'Follow Spot Light #002',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'SPOT002',
                'item_notes' => 'LED follow spot, energy efficient',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 8,
                'item_name' => 'Follow Spot Light #003',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'SPOT003',
                'item_notes' => 'Currently in theater production',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // --- VISUAL & CONFERENCE ---
            // Equipment ID 9: Projector (3200 Ansi Lumens) - 5 items
            [
                'equipment_id' => 9,
                'item_name' => 'Projector Unit #001',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'PROJ001',
                'item_notes' => '3200 Ansi Lumens projector with HDMI cables',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 9,
                'item_name' => 'Projector Unit #002',
                'status_id' => 2, // Reserved
                'condition_id' => 2, // Good
                'barcode_number' => 'PROJ002',
                'item_notes' => 'Backup projector unit, reserved for upcoming event',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 9,
                'item_name' => 'Projector Unit #003',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'PROJ003',
                'item_notes' => '4K laser projector, high brightness',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 9,
                'item_name' => 'Projector Unit #004',
                'status_id' => 4, // Maintenance
                'condition_id' => 4, // Needs Maintenance
                'barcode_number' => 'PROJ004',
                'item_notes' => 'Lamp hours exceeded, needs replacement',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 9,
                'item_name' => 'Projector Unit #005',
                'status_id' => 5, // Damaged
                'condition_id' => 5, // Damaged
                'barcode_number' => 'PROJ005',
                'item_notes' => 'Color wheel issue, awaiting parts',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 10: TV (65 inch) - 4 items
            [
                'equipment_id' => 10,
                'item_name' => '65-inch TV Unit #001',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'TV65001',
                'item_notes' => '65-inch Smart TV with wall mount',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 10,
                'item_name' => '65-inch TV Unit #002',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'TV65002',
                'item_notes' => 'OLED display, excellent color',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 10,
                'item_name' => '65-inch TV Unit #003',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'TV65003',
                'item_notes' => 'Installed in conference room A',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 10,
                'item_name' => '65-inch TV Unit #004',
                'status_id' => 2, // Reserved
                'condition_id' => 3, // Fair
                'barcode_number' => 'TV65004',
                'item_notes' => 'Minor scratch on bezel, screen perfect',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 11: Conference System (16 Delegates) - 3 items
            [
                'equipment_id' => 11,
                'item_name' => 'Conference System Set A',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'CONF001',
                'item_notes' => '16-delegate conference system with main unit',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 11,
                'item_name' => 'Conference System Set B',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'CONF002',
                'item_notes' => 'Wireless conference system with 20 mics',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 11,
                'item_name' => 'Conference System Set C',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'CONF003',
                'item_notes' => 'Currently set up in boardroom',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // --- MUSICAL INSTRUMENTS & ACCESSORIES ---
            // Equipment ID 12: Drum Set (Yamaha, 6 piece with throne) - 3 items
            [
                'equipment_id' => 12,
                'item_name' => 'Yamaha Drum Set #001',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'DRUM001',
                'item_notes' => '6-piece Yamaha drum set with throne and cymbals',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 12,
                'item_name' => 'Yamaha Drum Set #002',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'DRUM002',
                'item_notes' => 'Professional oak custom set',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 12,
                'item_name' => 'Yamaha Drum Set #003',
                'status_id' => 3, // In Use
                'condition_id' => 3, // Fair
                'barcode_number' => 'DRUM003',
                'item_notes' => 'Practice room set, heads need replacing soon',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 13: Guitar Amplifier (Bass, Guitar, Keyboard) - 4 items
            [
                'equipment_id' => 13,
                'item_name' => 'Guitar Amp Combo A',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'AMP001',
                'item_notes' => 'Multi-purpose amplifier for bass, guitar, and keyboard',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 13,
                'item_name' => 'Guitar Amp Combo B',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'AMP002',
                'item_notes' => 'High-power modeling amplifier',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 13,
                'item_name' => 'Guitar Amp Combo C',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'AMP003',
                'item_notes' => 'Currently at rehearsal studio',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 13,
                'item_name' => 'Guitar Amp Combo D',
                'status_id' => 4, // Maintenance
                'condition_id' => 4, // Needs Maintenance
                'barcode_number' => 'AMP004',
                'item_notes' => 'Input jack loose, needs repair',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 14: HDMI Splitter and Accessories - 5 items
            [
                'equipment_id' => 14,
                'item_name' => 'HDMI Splitter Kit A',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'HDMI001',
                'item_notes' => '4-port HDMI splitter with various cables',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 14,
                'item_name' => 'HDMI Splitter Kit B',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'HDMI002',
                'item_notes' => '8-port 4K HDMI splitter with optical audio',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 14,
                'item_name' => 'HDMI Splitter Kit C',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'HDMI003',
                'item_notes' => 'Connected in AV rack, main auditorium',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 14,
                'item_name' => 'HDMI Splitter Kit D',
                'status_id' => 1,
                'condition_id' => 3, // Fair
                'barcode_number' => 'HDMI004',
                'item_notes' => 'Older model, 1080p only',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 14,
                'item_name' => 'HDMI Splitter Kit E',
                'status_id' => 4, // Maintenance
                'condition_id' => 4, // Needs Maintenance
                'barcode_number' => 'HDMI005',
                'item_notes' => 'One port intermittent, needs check',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 15: Capture Card/Sound Card - 4 items
            [
                'equipment_id' => 15,
                'item_name' => 'Audio Interface Unit #001',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'AUDIO001',
                'item_notes' => 'USB audio interface with multiple inputs',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 15,
                'item_name' => 'Audio Interface Unit #002',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'AUDIO002',
                'item_notes' => 'Thunderbolt interface, low latency',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 15,
                'item_name' => 'Capture Card Unit #001',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'CAP001',
                'item_notes' => '4K HDMI capture card, currently in streaming setup',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 15,
                'item_name' => 'Capture Card Unit #002',
                'status_id' => 2, // Reserved
                'condition_id' => 1, // New
                'barcode_number' => 'CAP002',
                'item_notes' => 'Reserved for online event next week',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 16: Mic Stand - 5 items
            [
                'equipment_id' => 16,
                'item_name' => 'Mic Stand Set A (3 units)',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'STAND001',
                'item_notes' => 'Set of 3 microphone stands of different heights',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 16,
                'item_name' => 'Mic Stand Set B (3 units)',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'STAND002',
                'item_notes' => 'Heavy-duty round base stands',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 16,
                'item_name' => 'Mic Stand Set C (2 units)',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'STAND003',
                'item_notes' => 'Currently at podium setup',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 16,
                'item_name' => 'Mic Stand Set D (4 units)',
                'status_id' => 1,
                'condition_id' => 3, // Fair
                'barcode_number' => 'STAND004',
                'item_notes' => 'Older stands with some rust, fully functional',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 16,
                'item_name' => 'Boom Mic Stand (Single)',
                'status_id' => 5, // Damaged
                'condition_id' => 5, // Damaged
                'barcode_number' => 'STAND005',
                'item_notes' => 'Broken clutch mechanism, unusable',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 17: Keyboard - 3 items
            [
                'equipment_id' => 17,
                'item_name' => 'Digital Keyboard #001',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'KEYS001',
                'item_notes' => '61-key digital keyboard with stand',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 17,
                'item_name' => 'Digital Keyboard #002',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'KEYS002',
                'item_notes' => '88-key weighted stage piano',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 17,
                'item_name' => 'Digital Keyboard #003',
                'status_id' => 3, // In Use
                'condition_id' => 3, // Fair
                'barcode_number' => 'KEYS003',
                'item_notes' => 'Some sticky keys, but playable',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 18: Lapel Mic/Headworn Mic - 5 items
            [
                'equipment_id' => 18,
                'item_name' => 'Lapel Mic Set A (2 units)',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'LAPEL001',
                'item_notes' => 'Set of 2 lapel microphones with transmitters',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 18,
                'item_name' => 'Lapel Mic Set B (2 units)',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'LAPEL002',
                'item_notes' => 'Professional wireless lapel system',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 18,
                'item_name' => 'Headworn Mic Set A (2 units)',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'HEAD001',
                'item_notes' => 'Currently used in theater production',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 18,
                'item_name' => 'Lapel Mic Set C (2 units)',
                'status_id' => 4, // Maintenance
                'condition_id' => 4, // Needs Maintenance
                'barcode_number' => 'LAPEL003',
                'item_notes' => 'One transmitter has battery contact issues',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 18,
                'item_name' => 'Lapel Mic Single Unit',
                'status_id' => 5, // Damaged
                'condition_id' => 5, // Damaged
                'barcode_number' => 'LAPEL004',
                'item_notes' => 'Cable broken at connector',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 19: Wireless Mic - 5 items
            [
                'equipment_id' => 19,
                'item_name' => 'Wireless Mic Set A',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'WMIC001',
                'item_notes' => 'Dual wireless microphone system',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 19,
                'item_name' => 'Wireless Mic Set B',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'WMIC002',
                'item_notes' => '4-channel UHF wireless system',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 19,
                'item_name' => 'Wireless Mic Set C',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'WMIC003',
                'item_notes' => 'Currently used in main hall',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 19,
                'item_name' => 'Wireless Mic Set D',
                'status_id' => 2, // Reserved
                'condition_id' => 2, // Good
                'barcode_number' => 'WMIC004',
                'item_notes' => 'Reserved for conference',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 19,
                'item_name' => 'Wireless Mic Set E',
                'status_id' => 4, // Maintenance
                'condition_id' => 4, // Needs Maintenance
                'barcode_number' => 'WMIC005',
                'item_notes' => 'One receiver has weak signal',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Equipment ID 20: Communication System - 3 items
            [
                'equipment_id' => 20,
                'item_name' => 'Comm System Unit #001',
                'status_id' => 1,
                'condition_id' => 1, // New
                'barcode_number' => 'COMM001',
                'item_notes' => '4-channel communication system with headsets',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 20,
                'item_name' => 'Comm System Unit #002',
                'status_id' => 1,
                'condition_id' => 2, // Good
                'barcode_number' => 'COMM002',
                'item_notes' => '8-channel digital intercom system',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'equipment_id' => 20,
                'item_name' => 'Comm System Unit #003',
                'status_id' => 3, // In Use
                'condition_id' => 2, // Good
                'barcode_number' => 'COMM003',
                'item_notes' => 'Currently used for production crew',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}