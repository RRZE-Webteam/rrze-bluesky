import { useEffect, useState } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { __ } from "@wordpress/i18n";
import HeadingComponent from "./HeadingComponent";
import { RRZEVidstackPlayer as Vidstack } from "./Vidstack";
import { Notice } from "@wordpress/components";

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
      title?: string;
      description?: string;
      thumb?: string;
    };
    images?: Array<{
      thumb: string;
      alt: string;
    }>;
    playlist?: string;
    thumbnail?: string;
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

// Helper: convert author.handle -> bsky profile link
function getProfileUrl(handle: string): string {
  return `https://bsky.app/profile/${handle}`;
}

// Helper: build the bsky.app post link
function getPostUrl(handle: string, postUri: string): string {
  // last portion is typically the post's unique ID
  const postId = postUri.split("/").pop() || "";
  return `https://bsky.app/profile/${handle}/post/${postId}`;
}

function getDomainFromUrl(url: string): string {
  try {
    const { hostname } = new URL(url);
    return hostname;
  } catch (e) {
    console.log("Error parsing URL:", e);
    return url;
  }
}

interface PostProps {
  uri: string;
  hstart?: number;
}

interface Error {
  code: string;
  message: string;
  data: {
    status: number;
  };
}

export default function Post({ uri, hstart }: PostProps) {
  const [postData, setPostData] = useState<BskyPost | null>(null);
  const [error, setError] = useState<Error | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);

  useEffect(() => {
    setIsLoading(true);
    setError(null);

    // Example endpoint: /rrze-bluesky/v1/post?uri=...
    const path = `/rrze-bluesky/v1/post?uri=${encodeURIComponent(uri)}`;

    apiFetch({ path })
      .then((response: BskyPost) => {
        setPostData(response);
      })
      .catch((err: Error) => {
        setError(err);
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, [uri]);

  if (isLoading) {
    return <p>Loading post data...</p>;
  }

  if (error) {
    console.log(error);
    if (error.data.status === 401) {
      return (
        <>
          <Notice status="error" isDismissible={false}>
            <>
              Error:{" "}
              {__(
                "Authentification failed. Please make sure, that you entered your correct bsky credentials to connect to the Bluesky API.",
                "rrze-bluesky",
              )}
            </>
          </Notice>
          <Notice status="info" isDismissible={false}>
            <>
            <HeadingComponent style={{ fontSize: "2rem" }} level={hstart}>{__("How to login and connect to the Bsky API", "rrze-bluesky")}</HeadingComponent>
              <ol>
                <li>
                  <a
                    href="/wp-admin/options-general.php?page=rrze-bluesky"
                    target="_blank"
                  >
                    {__(
                      "Navigate Dashboard > Settings > RRZE Bluesky.",
                      "rrze-bluesky",
                    )}
                  </a>
                </li>
                <li>
                  {__(
                    "Enter your Bluesky credentials to establish a connection with the Bsky-API.",
                    "rrze-bluesky",
                  )}
                </li>
                <li>
                  {__(
                    "Refresh the current page or post inside the Blockeditor you are working on.",
                    "rrze-bluesky",
                  )}
                </li>
              </ol>
            </>
          </Notice>
        </>
      );
    }
    return (
      <Notice status="error" isDismissible={false}>
        Error: {error.message}
      </Notice>
    );
  }

  if (!postData) {
    return <p>No post data found.</p>;
  }

  const { author, record, embed, likeCount, replyCount, repostCount } =
    postData;

  const isVideoEmbed = embed?.$type === "app.bsky.embed.video#view";

  return (
    <article className="bsky-post">
      {/* Header Section: Author Info */}
      <header>
        <div className="author-information">
          <a href={getProfileUrl(author.handle)}>
            <img src={author.avatar} alt={author.displayName} />
          </a>

          <div className="author-name">
            <h3>
              <a href={getProfileUrl(author.handle)}>{author.displayName}</a>
            </h3>

            <p>
              <a href={getProfileUrl(author.handle)}>@{author.handle}</a>
            </p>
          </div>
        </div>

        <div className="bsky-branding">
          <a href={getProfileUrl(author.handle)}>
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
          </a>
        </div>
      </header>

      {/* Main Post Content */}
      <section className="bsky-post-content">
        <p>{record.text}</p>
        {embed?.images && embed.images.length > 0 && (
          <div className="bsky-image-gallery">
            {embed.images.map((img, idx) => {
              const altText = img.alt || "Bluesky embedded image";
              const thumb = img.thumb;
              return (
                <figure key={idx}>
                  {thumb ? (
                    <img src={thumb} alt={altText} />
                  ) : (
                    <p>[No image URL]</p>
                  )}
                </figure>
              );
            })}
          </div>
        )}

        {embed?.external && (
          <figure className="bsky-external-embed">
            {embed.external.thumb && (
              <img
                src={embed.external.thumb}
                alt={embed.external.title || "Bluesky embedded image"}
              />
            )}

            <figcaption
              className="bsky-embed-caption"
              aria-label={embed.external.title || ""}
              onClick={() => {
                if (embed.external.uri) {
                  window.open(
                    embed.external.uri,
                    "_blank",
                    "noopener,noreferrer",
                  );
                }
              }}
              style={{ cursor: embed.external.uri ? "pointer" : "default" }}
            >
              {embed.external.uri ? (
                <h4 className="bsky-external-heading">
                  <a
                    href={embed.external.uri}
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {embed.external.title || __("Untitled", "rrze-bluesky")}
                  </a>
                </h4>
              ) : (
                <h4 className="bsky-external-heading">
                  {embed.external.title || __("Untitled", "rrze-bluesky")}
                </h4>
              )}
              {embed.external.description && (
                <p className="bsky-external-teaser">
                  {embed.external.description}
                </p>
              )}

              {embed.external.uri && (
                <>
                  <hr />
                  <p className="bsky-external-domain-host">
                    {getDomainFromUrl(embed.external.uri)}
                  </p>
                </>
              )}
            </figcaption>
          </figure>
        )}
        {isVideoEmbed && (
          <div className="bsky-video">
            <Vidstack
              title="Test"
              mediaurl={embed?.playlist || ""}
              aspectratio="9/16"
              poster={embed?.thumbnail || ""}
            />
          </div>
        )}
      </section>

      {/* Footer: Post Stats */}
      <footer>
        <div className="publication-time">
          {author.createdAt && (
            <time dateTime={author.createdAt}>
              {new Date(author.createdAt).toLocaleDateString("de-DE", {
                day: "2-digit",
                month: "short",
                year: "numeric",
              })}{" "}
              {__("um", "rrze-bluesky")}{" "}
              {new Date(author.createdAt).toLocaleTimeString("de-DE", {
                hour: "2-digit",
                minute: "2-digit",
              })}
            </time>
          )}
        </div>

        <hr />

        <div className="bsky-stat-section">
          <div className="bsky-stat-icons">
            <div className="bsky-like-info">
              <svg
                className="bsky-like-icon"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 512 512"
              >
                <path
                  fill="currentColor"
                  d="M47.6 300.4L228.3 469.1c7.5 7 17.4 10.9 27.7 10.9s20.2-3.9 27.7-10.9L464.4 300.4c30.4-28.3 47.6-68 47.6-109.5v-5.8c0-69.9-50.5-129.5-119.4-141C347 36.5 300.6 51.4 268 84L256 96 244 84c-32.6-32.6-79-47.5-124.6-39.9C50.5 55.6 0 115.2 0 185.1v5.8c0 41.5 17.2 81.2 47.6 109.5z"
                />
              </svg>{" "}
              {likeCount}
            </div>

            <div className="bsky-retweet-info">
              <svg
                className="bsky-retweet-icon"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 576 512"
              >
                <path
                  fill="currentColor"
                  d="M272 416c17.7 0 32-14.3 32-32s-14.3-32-32-32l-112 0c-17.7 0-32-14.3-32-32l0-128 32 0c12.9 0 24.6-7.8 29.6-19.8s2.2-25.7-6.9-34.9l-64-64c-12.5-12.5-32.8-12.5-45.3 0l-64 64c-9.2 9.2-11.9 22.9-6.9 34.9s16.6 19.8 29.6 19.8l32 0 0 128c0 53 43 96 96 96l112 0zM304 96c-17.7 0-32 14.3-32 32s14.3 32 32 32l112 0c17.7 0 32 14.3 32 32l0 128-32 0c-12.9 0-24.6 7.8-29.6 19.8s-2.2 25.7 6.9 34.9l64 64c12.5 12.5 32.8 12.5 45.3 0l64-64c9.2-9.2 11.9-22.9 6.9-34.9s-16.6-19.8-29.6-19.8l-32 0 0-128c0-53-43-96-96-96L304 96z"
                />
              </svg>{" "}
              {repostCount}
            </div>

            <div className="bsky-comment-info">
              <a href={getPostUrl(author.handle, postData.uri)}>
                <svg
                  className="bsky-reply-icon"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 512 512"
                >
                  <path
                    fill="currentColor"
                    d="M64 0C28.7 0 0 28.7 0 64L0 352c0 35.3 28.7 64 64 64l96 0 0 80c0 6.1 3.4 11.6 8.8 14.3s11.9 2.1 16.8-1.5L309.3 416 448 416c35.3 0 64-28.7 64-64l0-288c0-35.3-28.7-64-64-64L64 0z"
                  />
                </svg>
              </a>
            </div>
          </div>

          <div className="bsky-reply">
            <a
              href={getPostUrl(author.handle, postData.uri)}
              className="bsky-reply-count"
            >
              {replyCount > 0
                ? `${__("Read", "rrze-bluesky")} ${replyCount} ${__(
                    "replies on Bluesky",
                    "rrze-bluesky",
                  )}`
                : __("Read on Bluesky", "rrze-bluesky")}
            </a>
          </div>
        </div>
      </footer>
    </article>
  );
}
