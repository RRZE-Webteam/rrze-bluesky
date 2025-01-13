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


namespace RRZE\Bluesky;

class Profil {
    public string $did;
    public string $handle;
    public ?string $createdAt;
    public ?string $indexedAt;
    public ?string $displayName;
    public ?string $description;
    public ?string $avatar;
    public ?string $banner;
    public int $followersCount;
    public int $followsCount;
    public int $postsCount;
    private ?Config $config;
    public ?array $labels;
    public ?array $pinnedPost;
    private array $rawdata;
    
    public function __construct(array $data, ?Config $config = null) {
        $this->did = $data['did'] ?? '';
        $this->handle = $data['handle'] ?? '';
        $this->displayName = $data['displayName'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->avatar = $data['avatar'] ?? '';
        $this->banner = $data['banner'] ?? '';
        $this->createdAt = $data['createdAt'] ?? '';
        $this->indexedAt = $data['indexedAt'] ?? '';
        $this->labels = $data['labels'] ?? null;
        $this->pinnedPost = $data['pinnedPost'] ?? null;
        
        $this->followersCount = (int) ($data['followersCount'] ?? 0);
        $this->followsCount= (int) ($data['followsCount'] ?? 0);
        $this->postsCount= (int) ($data['postsCount'] ?? 0);
        $this->config = $config ?? null;
        
        // Everything else move in rawdata       
        $usedKeys = [
            'did',
            'handle',
            'displayName',
            'description',
            'avatar',
            'banner',
            'createdAt',
            'indexedAt',
            'followersCount',
            'followsCount',
            'postsCount',
            'labels',
            'pinnedPost'
        ];
        $remaining = array_diff_key($data, array_flip($usedKeys));
        $this->rawdata = $remaining;
    }
    
    public function setConfig(Config $config) {
        return $this->config = $config;
    }
       
    
    public function getRawData(): array {
        return $this->rawdata;
    }
    
    public function getProfilView(?string $template = null): string {
        if (empty($template)) {
            $template = '';
            $template .= "Handle     : #handle#".PHP_EOL;
            $template .= "DID        : #did#".PHP_EOL;
            $template .= "DisplayName: #displayName#".PHP_EOL;
            $template .= "Avatar     : #avatar#".PHP_EOL;
            $template .= "Banner     : #banner#".PHP_EOL;
            $template .= "Stats      : #followersCount# Follower, #followsCount# Follows, #postCount# Posts".PHP_EOL;
            $template .= "Created    : #created#".PHP_EOL;
            $template .= "Bluesky URL: #blueskyurl#".PHP_EOL;
            $template .= "XRPC URL   : #xrpcurl#".PHP_EOL;
            $template .= "Description: #description#".PHP_EOL;
        }
        

        // Platzhalter mit den entsprechenden Werten ersetzen
        $replacements = [
            '#handle#' => $this->handle ?? 'N/A',
            '#did#' => $this->did ?? 'N/A',
            '#created#' => $this->createdAt ?? 'N/A',
            '#indexedat#' => $this->indexedAt ?? 'N/A',
            '#description#' => $this->description ?? 'N/A',
            '#followersCount#' => $this->followersCount ?? '0',
            '#followsCount#' => $this->followsCount ?? '0',
            '#postCount#' => $this->postCount ?? '0',
            '#avatar#' => $this->avatar ?? 'N/A',
            '#banner#' => $this->banner ?? 'N/A',
            '#blueskyurl#'  => $this->getBlueskyURL(),
            '#xrpcurl#' => $this->getBlueSkyXRPCURL()
        ];

                
        return str_replace(array_keys($replacements), array_values($replacements), $template);

    }
    
    /*
     * Get public Bluesky URL for profil
     */
    public function getBlueskyURL(): string {       
        return "https://bsky.app/profile/{$this->handle}";
    }
     /**
     * get XRPC URL for profile
     */
    public function getBlueSkyXRPCURL(): string {
        return "https://public.api.bsky.app/xrpc/app.bsky.actor.getProfile?actor=" . urlencode($this->did);
    }
}