<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StoryAudio;

class StoryAudioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $audioFiles = [
            // Background Music
            [
                'name' => 'Chill Vibes',
                'filename' => 'chill_vibes.mp3',
                'path' => 'story_audio/background/chill_vibes.mp3',
                'duration' => 180, // 3 minutes
                'file_size' => 4320000, // ~4.3MB
                'mime_type' => 'audio/mpeg',
                'image' => 'story_audio/thumbnails/chill_vibes.jpg',
                'status' => 'active',
                'category' => 'background'
            ],
            [
                'name' => 'Upbeat Energy',
                'filename' => 'upbeat_energy.mp3',
                'path' => 'story_audio/background/upbeat_energy.mp3',
                'duration' => 150, // 2.5 minutes
                'file_size' => 3600000, // ~3.6MB
                'mime_type' => 'audio/mpeg',
                'image' => 'story_audio/thumbnails/upbeat_energy.jpg',
                'status' => 'active',
                'category' => 'background'
            ],
            [
                'name' => 'Relaxing Nature',
                'filename' => 'relaxing_nature.mp3',
                'path' => 'story_audio/background/relaxing_nature.mp3',
                'duration' => 240, // 4 minutes
                'file_size' => 5760000, // ~5.76MB
                'mime_type' => 'audio/mpeg',
                'image' => 'story_audio/thumbnails/relaxing_nature.jpg',
                'status' => 'active',
                'category' => 'background'
            ],

            // Sound Effects
            [
                'name' => 'Camera Click',
                'filename' => 'camera_click.wav',
                'path' => 'story_audio/effects/camera_click.wav',
                'duration' => 1, // 1 second
                'file_size' => 44100, // ~44KB
                'mime_type' => 'audio/wav',
                'image' => 'story_audio/thumbnails/camera_click.jpg',
                'status' => 'active',
                'category' => 'effect'
            ],
            [
                'name' => 'Heart Beat',
                'filename' => 'heart_beat.wav',
                'path' => 'story_audio/effects/heart_beat.wav',
                'duration' => 2, // 2 seconds
                'file_size' => 88200, // ~88KB
                'mime_type' => 'audio/wav',
                'image' => 'story_audio/thumbnails/heart_beat.jpg',
                'status' => 'active',
                'category' => 'effect'
            ],
            [
                'name' => 'Notification Bell',
                'filename' => 'notification_bell.wav',
                'path' => 'story_audio/effects/notification_bell.wav',
                'duration' => 1, // 1 second
                'file_size' => 44100, // ~44KB
                'mime_type' => 'audio/wav',
                'image' => 'story_audio/thumbnails/notification_bell.jpg',
                'status' => 'active',
                'category' => 'effect'
            ],
            [
                'name' => 'Pop Sound',
                'filename' => 'pop_sound.wav',
                'path' => 'story_audio/effects/pop_sound.wav',
                'duration' => 1, // 1 second
                'file_size' => 44100, // ~44KB
                'mime_type' => 'audio/wav',
                'image' => 'story_audio/thumbnails/pop_sound.jpg',
                'status' => 'active',
                'category' => 'effect'
            ],

            // Music
            [
                'name' => 'Happy Birthday',
                'filename' => 'happy_birthday.mp3',
                'path' => 'story_audio/music/happy_birthday.mp3',
                'duration' => 30, // 30 seconds
                'file_size' => 720000, // ~720KB
                'mime_type' => 'audio/mpeg',
                'image' => 'story_audio/thumbnails/happy_birthday.jpg',
                'status' => 'active',
                'category' => 'music'
            ],
            [
                'name' => 'Wedding March',
                'filename' => 'wedding_march.mp3',
                'path' => 'story_audio/music/wedding_march.mp3',
                'duration' => 45, // 45 seconds
                'file_size' => 1080000, // ~1.08MB
                'mime_type' => 'audio/mpeg',
                'image' => 'story_audio/thumbnails/wedding_march.jpg',
                'status' => 'active',
                'category' => 'music'
            ],
            [
                'name' => 'Graduation Theme',
                'filename' => 'graduation_theme.mp3',
                'path' => 'story_audio/music/graduation_theme.mp3',
                'duration' => 60, // 1 minute
                'file_size' => 1440000, // ~1.44MB
                'mime_type' => 'audio/mpeg',
                'image' => 'story_audio/thumbnails/graduation_theme.jpg',
                'status' => 'active',
                'category' => 'music'
            ],

            // Voice/Narration
            [
                'name' => 'Welcome Message',
                'filename' => 'welcome_message.mp3',
                'path' => 'story_audio/voice/welcome_message.mp3',
                'duration' => 15, // 15 seconds
                'file_size' => 360000, // ~360KB
                'mime_type' => 'audio/mpeg',
                'image' => 'story_audio/thumbnails/welcome_message.jpg',
                'status' => 'active',
                'category' => 'voice'
            ],
            [
                'name' => 'Motivational Quote',
                'filename' => 'motivational_quote.mp3',
                'path' => 'story_audio/voice/motivational_quote.mp3',
                'duration' => 20, // 20 seconds
                'file_size' => 480000, // ~480KB
                'mime_type' => 'audio/mpeg',
                'image' => 'story_audio/thumbnails/motivational_quote.jpg',
                'status' => 'active',
                'category' => 'voice'
            ],
            [
                'name' => 'Funny Commentary',
                'filename' => 'funny_commentary.mp3',
                'path' => 'story_audio/voice/funny_commentary.mp3',
                'duration' => 25, // 25 seconds
                'file_size' => 600000, // ~600KB
                'mime_type' => 'audio/mpeg',
                'image' => 'story_audio/thumbnails/funny_commentary.jpg',
                'status' => 'active',
                'category' => 'voice'
            ],

            // Additional Background Music
            [
                'name' => 'Lo-Fi Study',
                'filename' => 'lofi_study.mp3',
                'path' => 'story_audio/background/lofi_study.mp3',
                'duration' => 200, // 3.33 minutes
                'file_size' => 4800000, // ~4.8MB
                'mime_type' => 'audio/mpeg',
                'image' => 'story_audio/thumbnails/lofi_study.jpg',
                'status' => 'active',
                'category' => 'background'
            ],
            [
                'name' => 'Sunset Calm',
                'filename' => 'sunset_calm.mp3',
                'path' => 'story_audio/background/sunset_calm.mp3',
                'duration' => 165, // 2.75 minutes
                'file_size' => 3960000, // ~3.96MB
                'mime_type' => 'audio/mpeg',
                'image' => 'story_audio/thumbnails/sunset_calm.jpg',
                'status' => 'active',
                'category' => 'background'
            ],

            // Additional Effects
            [
                'name' => 'Applause',
                'filename' => 'applause.wav',
                'path' => 'story_audio/effects/applause.wav',
                'duration' => 5, // 5 seconds
                'file_size' => 220500, // ~220KB
                'mime_type' => 'audio/wav',
                'image' => 'story_audio/thumbnails/applause.jpg',
                'status' => 'active',
                'category' => 'effect'
            ],
            [
                'name' => 'Rain Drops',
                'filename' => 'rain_drops.wav',
                'path' => 'story_audio/effects/rain_drops.wav',
                'duration' => 10, // 10 seconds
                'file_size' => 441000, // ~441KB
                'mime_type' => 'audio/wav',
                'image' => 'story_audio/thumbnails/rain_drops.jpg',
                'status' => 'active',
                'category' => 'effect'
            ]
        ];

        foreach ($audioFiles as $audio) {
            StoryAudio::create($audio);
        }
    }
} 