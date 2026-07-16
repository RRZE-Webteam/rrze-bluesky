<?php

namespace RRZE\Bluesky\Model;

defined('ABSPATH') || exit;

/**
 * Data model representing a single Bluesky post.
 */
class Post
{
  public string $text;
  public string $uri;
  public string $cid;
  public string $createdAt;
  public string $indexedAt;
  public ?Profile $author;
  public ?array $embed;
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


  /**
   * @param array $data Raw post data array from the Bluesky API.
   */
  public function __construct(array $data)
  {

    $this->uri = $data['uri'] ?? '';
    $this->cid = $data['cid'] ?? '';
    $this->createdAt = $data['record']['createdAt'] ?? '';
    $this->langs = $data['record']['langs'] ?? null;
    $this->text = $data['record']['text'] ?? '';
    $this->embeds = $data['record']['embed'] ?? null;
    $this->facets = $data['record']['facets'] ?? null;
    $this->embed = $data['embed'] ?? null;

    $this->indexedAt = $data['indexedAt'] ?? '';
    $this->likeCount = (int)($data['likeCount'] ?? 0);
    $this->repostCount = (int)($data['repostCount'] ?? 0);
    $this->replyCount = (int)($data['replyCount'] ?? 0);
    $this->quoteCount = (int)($data['quoteCount'] ?? 0);

    $this->author = new Profile($data['author']);
    $this->viewer = $data['viewer'] ?? null;
    $this->labels = $data['labels'] ?? null;

    // Store unmapped fields for extensibility
    $usedKeys = [
      'text',
      'uri',
      'cid',
      'record',
      'author',
      'embed',
      'indexedAt',
      'likeCount',
      'repostCount',
      'quoteCount',
      'replyCount',
      'viewer',
      'labels'
    ];
    $this->rawdata = array_diff_key($data,
      array_flip($usedKeys));
  }


  /**
   * Returns the handle of the post author.
   *
   * @return string|null The author's handle, or null if not set.
   */
  public function getAuthorHandle(): ?string
  {
    return $this->author->handle ?? null;
  }


  /**
   * Extracts hashtags from the post facets.
   *
   * @return array List of hashtag strings (e.g. ['#php', '#wordpress']), empty array if none.
   */
  public function getTags(): array
  {
    $taglist = [];
    if ($this->facets) {
      foreach ($this->facets as $num) {
        if (isset($num['features'][0]['tag'])) {
          $taglist[] = '#' . $num['features'][0]['tag'];
        }
      }
    }
    return $taglist;
  }


  /**
   * Returns the public Bluesky URL for this post.
   *
   * @return string Public Bluesky post URL.
   * @throws \InvalidArgumentException If the post URI does not match the expected AT URI format.
   */
  public function getBlueskyURL(): string {
    if (!preg_match('/^at:\/\/did:plc:([a-z0-9]+)\/app\.bsky\.feed\.post\/([a-z0-9]+)$/', $this->uri, $matches)) {
      throw new \InvalidArgumentException("Invalid URI: "
        . $this->uri);
    }
    $profileId = $this->getAuthorHandle() ?? $matches[0];
    $postId    = $matches[2];
    return
      "https://bsky.app/profile/{$profileId}/post/{$postId}";
  }


  /**
   * Returns the XRPC URL for fetching this post.
   *
   * @return string XRPC API URL.
   */
  public function getBlueSkyXRPCURL(): string {
    return
      "https://public.api.bsky.app/xrpc/app.bsky.feed.getPosts?uris="
      . urlencode($this->uri);
  }

}
