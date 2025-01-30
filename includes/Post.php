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

class Post {
    public string $text;
    public string $uri;
    public string $cid;
    public string $createdAt;
    public string $indexedAt;
    public ?Profil $author;
    public ?array $embeds;
    public ?array $facets;
    public ?array $langs;
    public int $likeCount;
    public int $repostCount;
    public int $replyCount;
    public int $quoteCount;
    public ?array $viewer;
    public ?array $labels;
    private array $rawdata;
    
    public function __construct(array $data) {
        
        // echo var_dump($data);
        
        
        $this->uri = $data['uri'] ?? '';
        $this->cid = $data['cid'] ?? '';
        $this->createdAt = $data['record']['createdAt'] ?? '';
        $this->langs = $data['record']['langs'] ?? null;
        $this->text = $data['record']['text'] ?? '';
        $this->embeds = $data['record']['embed'] ?? null;
        $this->facets = $data['record']['facets'] ?? null;     
        
        $this->indexedAt = $data['indexedAt'] ?? '';
        $this->likeCount = (int) ($data['likeCount'] ?? 0);
        $this->repostCount= (int) ($data['repostCount'] ?? 0);
        $this->replyCount= (int) ($data['replyCount'] ?? 0);
        $this->quoteCount= (int) ($data['quoteCount'] ?? 0);       
        
        $this->author = new Profil($data['author']);
        $this->viewer = $data['viewer'] ?? null;    
        $this->labels = $data['labels'] ?? null;   
        
        // Everything else move in rawdata       
        $usedKeys = [
            'text',
            'uri',
            'cid',
            'record',
            'author',
            'indexedAt',
            'likeCount',
            'repostCount',
            'quoteCount',
            'replyCount',
            'viewer',
            'labels'
        ];
        $remaining = array_diff_key($data, array_flip($usedKeys));
        $this->rawdata = $remaining;
        
    }
    
    public function getAutorHandle(): ?string {
        return $this->author->handle ?? null;
    }

  
 
    public function getTags(): ?array {
        $taglist = [];
        if ($this->facets) {
            foreach ($this->facets as $num) {
                if (isset($num['features'][0]['tag'])) {
                    $taglist[] = '#'.$num['features'][0]['tag'];
                }
            }
        }
        
        return $taglist;
    }
    
    
    
    public function getRawData(): array {
        return $this->rawdata;
    }
    
    public function getPostView(?string $template = null): string {
        if (empty($template)) {
            $template = '';
            $template .= "Text (ex.) : #textexcerpt#".PHP_EOL;
            $template .= "Tags       : #tags#".PHP_EOL;
            $template .= "Author     : #author#".PHP_EOL;
            $template .= "Created at : #created#".PHP_EOL;
            $template .= "Stats      : #likes# Likes, #reposts# Reposts, #replys# Replys".PHP_EOL;
            $template .= "Bluesky URL: #blueskyurl#".PHP_EOL;
            $template .= "XRPC URL   : #xrpcurl#".PHP_EOL;
        }
        
        $limit = 80;

        $textExcerpt = $this->text ? substr(str_replace(["\r", "\n"], ' ', $this->text), 0, $limit)
        : 'N/A';
        
        // Platzhalter mit den entsprechenden Werten ersetzen
        $replacements = [
            '#author#' => $this->getAutorHandle() ?? 'N/A',
            '#text#' => $this->text ?? 'N/A',
            '#textexcerpt#' => $textExcerpt,
            '#created#' => $this->createdAt ?? 'N/A',
            '#id#' => $this->uri ?? 'N/A',
            '#tags#' => implode(', ', $this->getTags() ?? []),
            '#reposts#' => $this->repostCount ?? '0',
            '#replys#' => $this->replyCount ?? '0',
            '#quotes#' => $this->quoteCount ?? '0',
            '#likes#' => $this->likeCount ?? '0',
            '#blueskyurl#'  => $this->getBlueskyURL() ?? '',
            '#xrpcurl#' => $this->getBlueSkyXRPCURL() ?? ''
        ];

                
        return str_replace(array_keys($replacements), array_values($replacements), $template);

    }
    
     /**
     * gets public Bluesky-URL for post
     */
    public function getBlueskyURL(): string {
        // Überprüfen, ob die URI das richtige Format hat
        if (!preg_match('/^at:\/\/did:plc:([a-z0-9]+)\/app\.bsky\.feed\.post\/([a-z0-9]+)$/', $this->uri, $matches)) {
            throw new \InvalidArgumentException("Invalid URI: ".$this->uri);
        }
        if ($this->getAutorHandle()) {
            $profileId = $this->getAutorHandle();
        } else {
            $profileId = $matches[0];
        }
        $postId = $matches[2];

        return "https://bsky.app/profile/{$profileId}/post/{$postId}";
    }

    /**
     * gets XRPC URL for post
     */
    public function getBlueSkyXRPCURL(): string {
        return "https://public.api.bsky.app/xrpc/app.bsky.feed.getPosts?uris=" . urlencode($this->uri);
    }
}