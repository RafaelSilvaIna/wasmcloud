<?php
class AvatarHelper {
    public static function getAvatarCategories(): array {
        return ['adventurer', 'avataaars', 'bottts', 'pixel-art', 'open-peeps', 'notionists'];
    }

    public static function generateRandomAvatars(string $category, int $count = 12): array {
        $avatars = [];
        for ($i = 0; $i < $count; $i++) {
            $seed = bin2hex(random_bytes(5));
            $avatars[] = "https://api.dicebear.com/7.x/{$category}/svg?seed={$seed}";
        }
        return $avatars;
    }

    public static function generate(string $category, int $count = 12): array {
        return self::generateRandomAvatars($category, $count);
    }
}