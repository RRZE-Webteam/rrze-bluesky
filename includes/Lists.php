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

class Lists {
    public string $uri;
    public string $cid;
    public Profil $creator;
    public string $name;
    public string $purpose;
    public string $indexedAt;
    public ?string $description;
    public ?array $descriptionFacets;
    public ?string $avatar;
    public int $listItemCount;
    public ?array $labels;
    public ?array $viewer;
    private ?array $rawdata;
    
    public function __construct(array $data) {
        $this->uri = $data['uri'] ?? '';
        $this->cid = $data['cid'] ?? '';
        $this->creator = new Profil($data['creator']);
        $this->description = $data['description'] ?? '';
        $this->avatar = $data['avatar'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->indexedAt = $data['indexedAt'] ?? '';
        $this->labels = $data['labels'] ?? null;
        $this->viewer = $data['viewer'] ?? null;
        $this->purpose = $data['purpose'] ?? null;
        $this->descriptionFacets = $data['descriptionFacets'] ?? null;
        $this->listItemCount = (int) ($data['listItemCount'] ?? 0);     
        
        // Everything else move in rawdata       
        $usedKeys = [
            'uri',
            'cid',
            'name',
            'creator',
            'avatar',
            'purpose',
            'indexedAt',
            'description',
            'descriptionFacets',
            'listItemCount',
            'viewer',
            'labels'
        ];
        $remaining = array_diff_key($data, array_flip($usedKeys));
        $this->rawdata = $remaining;
    }
    
    public function getRawData(): array {
        return $this->rawdata;
    }
    
    public function getListsView(?string $template = null): string {
        if (empty($template)) {
            $template = '';
            $template .= "Name         : #name#".PHP_EOL;
            $template .= "Creator      : #creator#".PHP_EOL;
            $template .= "URI          : #uri#".PHP_EOL;
            $template .= "CID          : #cid#".PHP_EOL;
            $template .= "Avatar       : #avatar#".PHP_EOL;
            $template .= "ListItemCount: #listItemCount#".PHP_EOL;
            $template .= "Bluesky URL  : #blueskyurl#".PHP_EOL;
            $template .= "XRPC URL     : #xrpcurl#".PHP_EOL;
            $template .= "Description  : #description#".PHP_EOL;
        }
        
         
        // Platzhalter mit den entsprechenden Werten ersetzen
        $replacements = [
            '#name#' => $this->name ?? 'N/A',
            '#creator#' => $this->getCreatorHandle(),
            '#uri#' => $this->uri ?? 'N/A',
            '#cid#' => $this->cid ?? 'N/A',
            '#indexedat#' => $this->indexedAt ?? 'N/A',
            '#description#' => $this->description ?? 'N/A',
            '#listItemCount#' => $this->listItemCount ?? '0',
            '#avatar#' => $this->avatar ?? 'N/A',
            '#blueskyurl#'  => $this->getBlueskyURL(),
            '#xrpcurl#' => $this->getBlueSkyXRPCURL()
        ];

                
        return str_replace(array_keys($replacements), array_values($replacements), $template);

    }
    
    /*
     * get handle of creator
     */
    public function getCreatorHandle(): ?string {
        return $this->creator->handle ?? null;
    }
    
    /**
     * gets public Bluesky-URL for list
     */
    public function getBlueskyURL(): string {
        // Überprüfen, ob die URI das richtige Format hat
        if (!preg_match('/^at:\/\/did:plc:([a-z0-9]+)\/app\.bsky\.graph\.list\/([a-z0-9]+)$/', $this->uri, $matches)) {
            throw new \InvalidArgumentException("Invalid URI: ".$this->uri);
        }
        if ($this->getCreatorHandle()) {
            $profileId = $this->getCreatorHandle();
        } else {
            $profileId = $matches[0];
        }
        $postId = $matches[2];

        return "https://bsky.app/profile/{$profileId}/lists/{$postId}";
    }
     /**
     * get XRPC URL for profile
     */
    public function getBlueSkyXRPCURL(): string {
        return "https://public.api.bsky.app/xrpc/app.bsky.graph.getList?list=" . urlencode($this->uri);
    }
}