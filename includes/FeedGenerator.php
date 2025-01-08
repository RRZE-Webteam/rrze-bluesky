<?php

/*
 * Copyright (C) 2025 xwolf
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Generating Feed out of filtered posts
 *
 * @author Anwender
 */

namespace Bluesky;

class FeedGenerator {
     // Beispiel-Feed-Daten
    private $posts = [
        [
            'uri' => 'at://did:plc:12345/app.bsky.feed.post/1',
            'cid' => 'bafyre...123',
            'author' => 'did:plc:12345',
            'text' => 'Welcome to #Bluesky! #WordPress',
        ],
        [
            'uri' => 'at://did:plc:67890/app.bsky.feed.post/2',
            'cid' => 'bafyre...456',
            'author' => 'did:plc:67890',
            'text' => 'Check out #Gutenberg and #ClassicPress!',
        ],
    ];

    // Liefert den Feed-Skeleton
    public function getFeedSkeleton() {
        $filteredPosts = array_filter($this->posts, function ($post) {
            // Nur Beiträge mit bestimmten Hashtags
            $hashtags = ['#WordPress', '#Gutenberg', '#ClassicPress'];
            foreach ($hashtags as $hashtag) {
                if (strpos($post['text'], $hashtag) !== false) {
                    return true;
                }
            }
            return false;
        });

        // Formatiere das Ergebnis im Bluesky-Schema
        $skeleton = [
            'feed' => array_map(function ($post) {
                return [
                    'post' => $post['uri'],
                    'reason' => null, // Optional
                ];
            }, $filteredPosts),
        ];
        error_log("Feed Skeleton received at " . date('Y-m-d H:i:s'));
        return $skeleton;
    }
}