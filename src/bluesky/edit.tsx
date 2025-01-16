// Imports from WordPress libraries
import { useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import apiFetch from "@wordpress/api-fetch";
import { useEffect, useState } from "@wordpress/element";

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
        } = post;

        return (
          <article className="bsky-post" key={cid || index}>
            {/* Header Section: Author Info */}
            <header>
              <div className="author-information">
                <div>
                  <img src={author.avatar} alt={author.displayName} />
                </div>
                <div className="author-name">
                  <h3>{author.displayName}</h3>
                  <p>@{author.handle}</p>
                </div>
              </div>
              <div className="bsky-branding">
                <svg
                  className="bsky-logo"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 568 501"
                >
                  <title>Bluesky butterfly logo</title>
                  <path
                    fill="currentColor"
                    d="M123.121 33.664C188.241 82.553 258.281 181.68 284 234.873c25.719-53.192 95.759-152.32 160.879-201.21C491.866-1.611 568-28.906 568 57.947c0 17.346-9.945 145.713-15.778 166.555-20.275 72.453-94.155 90.933-159.875 79.748C507.222 323.8 536.444 388.56 473.333 453.32c-119.86 122.992-172.272-30.859-185.702-70.281-2.462-7.227-3.614-10.608-3.631-7.733-.017-2.875-1.169.506-3.631 7.733-13.43 39.422-65.842 193.273-185.702 70.281-63.111-64.76-33.89-129.52 80.986-149.071-65.72 11.185-139.6-7.295-159.875-79.748C9.945 203.659 0 75.291 0 57.946 0-28.906 76.135-1.612 123.121 33.664Z"
                  ></path>
                </svg>
              </div>
            </header>

            {/* Main Post Content */}
            <section className="bsky-post-content">
              <p>{record.text}</p>

              {/* Example: If there’s an embed with an external thumb */}
              {embed?.external && (
                <figure>
                  <img
                    src={embed.external.thumb}
                    alt={embed.external.description || "Embedded image"}
                  />
                </figure>
              )}
            </section>

            {/* Footer: Post Stats */}
            <footer>
              <div className="publication-time">
                {author.createdAt && (
                  <time dateTime={author.createdAt}>
                    {new Date(author.createdAt).toLocaleString()}
                  </time>
                )}
              </div>
              <hr />
              <p>
                Likes: {likeCount} | Reposts: {repostCount} | Reply
              </p>
              <p className="bsky-reply-count">
                {__("Read", "rrze-bluesky")} {replyCount}{" "}
                {__("replies on Bluesky", "rrze-bluesky")}
              </p>
            </footer>
          </article>
        );
      })}
    </div>
  );
}
