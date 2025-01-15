// Imports from WordPress libraries
import {
  useBlockProps,
} from "@wordpress/block-editor";

import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

export interface BskyFeed {
  feed: Array<{
    post: BskyPost;
  }>;
}

export interface BskyPost {
  uri: string;
  cid: string;

  author: {
    did: string;
    handle: string;
    displayName: string;
    avatar: string;
    viewer: {
      muted: boolean;
      blockedBy: boolean;
    };
    labels: string[];
    createdAt?: string;
  };

  record: {
    $type?: string;
    createdAt: string;
    embed?: {
      $type: string;
      external: {
        description: string;
        thumb: {
          $type?: string;
          ref: {
            $link: string;
          };
          mimeType: string;
          size: number;
        };
        title: string;
        uri: string;
      };
    };
    facets?: Array<{
      features: Array<{
        $type: string;
        uri: string;
      }>;
      index: {
        byteStart: number;
        byteEnd: number;
      };
    }>;
    langs?: string[];
    text: string;
  };

  embed?: {
    $type: string;
    external: {
      uri: string;
      title: string;
      description: string;
      thumb: string; 
    };
  };

  replyCount: number;
  repostCount: number;
  likeCount: number;
  quoteCount: number;
  indexedAt: string;

  viewer: {
    threadMuted: boolean;
    replyDisabled: boolean;
    embeddingDisabled: boolean;
  };

  labels: string[];
  threadgate?: {
    uri: string;
    cid: string;
    record: {
      $type: string;
      allow: string[];
      createdAt: string;
      hiddenReplies: string[];
      post: string;
    };
    lists: string[];
  };
}


export default function Edit() {
  const [feedData, setFeedData] = useState<BskyFeed | null>(null);
  const [error, setError] = useState<Error | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);

  const blockProps = useBlockProps();

  useEffect(() => {
    setIsLoading(true);
    apiFetch({ path: "/rrze-bluesky/v1/public-timeline" })
      .then((response: BskyFeed) => {
        console.log(response);
        setFeedData(response);
        setError(null);
      })
      .catch((err: Error) => {
        setError(err);
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, []);

  if (isLoading) {
    return (
      <div {...blockProps}>
        <p>Loading feed data...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div {...blockProps}>
        <p>Error: {error.message}</p>
      </div>
    );
  }

  if (!feedData || !feedData.feed?.length) {
    return (
      <div {...blockProps}>
        <p>No feed data available.</p>
      </div>
    );
  }

  return (
    <div {...blockProps}>
      <h2>Bluesky Public Timeline</h2>
      {feedData.feed.map(({ post }, index) => {
        const {
          cid,
          author,
          record,
          embed,
          replyCount,
          repostCount,
          likeCount,
          quoteCount,
          indexedAt,
        } = post;

        return (
          <article key={cid || index}>
            {/* Header Section: Author Info */}
            <header>
              <h3>{author.displayName || author.handle}</h3>
              <p>DID: {author.did}</p>
              {author.createdAt && (
                <time dateTime={author.createdAt}>
                  {new Date(author.createdAt).toLocaleString()}
                </time>
              )}
            </header>

            {/* Main Post Content */}
            <section>
              <p>{record.text}</p>

              {/* Example: If there’s an embed with an external thumb */}
              {embed?.external && (
                <figure>
                  <img
                    src={embed.external.thumb}
                    alt={embed.external.description || "Embedded image"}
                  />
                  <figcaption>{embed.external.title}</figcaption>
                </figure>
              )}
            </section>

            {/* Footer: Post Stats */}
            <footer>
              <p>
                Likes: {likeCount} | Reposts: {repostCount} | Replies: {replyCount} | Quotes: {quoteCount}
              </p>
              <small>Indexed at: {new Date(indexedAt).toLocaleString()}</small>
            </footer>
          </article>
        );
      })}
    </div>
  );
}