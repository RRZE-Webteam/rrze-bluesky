// Post.tsx
import { useEffect, useState } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { __ } from "@wordpress/i18n";
import { Notice } from "@wordpress/components";

export interface IStarterPackRoot {
  starterPack: IStarterPack;
}

interface Error {
  code: string;
  message: string;
  data: {
    status: number;
  };
}

/**
 * The object under the "starterPack" key.
 */
export interface IStarterPack {
  uri: string;
  cid: string;
  record: IRecord;
  creator: ICreator;
  list: IList;
  listItemsSample: IListItemSample[];
  feeds: IFeed[];
  joinedWeekCount: number;
  joinedAllTimeCount: number;
  labels: ILabel[];
  indexedAt: string; // date-time
}

/**
 * The `record` field is shown as an empty object in your example,
 * but has a $type in reality ("app.bsky.graph.starterpack").
 * Use a generic type if needed:
 */
export interface IRecord {
  // Adjust as needed if you have more specific fields:
  [key: string]: unknown;
}

/**
 * The "creator" object in the starter pack
 */
export interface ICreator {
  did: string;
  handle: string;
  displayName: string;
  avatar: string;
  associated: IAssociated;
  viewer: IViewer;
  labels: ILabel[];
  createdAt: string; // date-time
}

/**
 * The "list" object in the starter pack
 */
export interface IList {
  uri: string;
  cid: string;
  name: string;
  purpose: string;
  avatar?: string;
  listItemCount: number;
  labels: ILabel[];
  viewer: {
    muted: boolean;
    blocked: string;
  };
  indexedAt: string; // date-time
}

/**
 * Each item in "listItemsSample" array
 */
export interface IListItemSample {
  uri: string;
  subject: ISubject;
}

/**
 * The "subject" object inside each list item
 */
export interface ISubject {
  did: string;
  handle: string;
  displayName: string;
  description?: string;
  avatar?: string;
  associated: IAssociated;
  indexedAt: string; // date-time
  createdAt?: string; // date-time
  viewer: IViewer;
  labels: ILabel[];
}

/**
 * "feeds" array items
 */
export interface IFeed {
  uri: string;
  cid: string;
  did: string;
  creator: ICreatorWithDescription;
  displayName: string;
  description?: string;
  descriptionFacets?: IDescriptionFacet[];
  avatar?: string;
  likeCount?: number;
  acceptsInteractions: boolean;
  labels: ILabel[];
  viewer: {
    like?: string;
  };
  indexedAt: string; // date-time
}

/**
 * The "creator" object within a feed has extra description fields
 */
export interface ICreatorWithDescription extends ICreator {
  description?: string;
  indexedAt: string; // date-time
  createdAt: string; // date-time
}

/**
 * "descriptionFacets" array inside a feed
 */
export interface IDescriptionFacet {
  index: {
    byteStart: number;
    byteEnd: number;
  };
  features: Array<{ did: string } | { uri: string } | { tag: string }>;
}

/**
 * "labels" array item
 */
export interface ILabel {
  ver: number;
  src: string;
  uri: string;
  cid: string;
  val: string;
  neg: boolean;
  cts: string; // date-time
  exp?: string; // date-time
  sig?: string;
}

/**
 * "associated" object (lists, feedgens, etc.)
 */
export interface IAssociated {
  lists: number;
  feedgens: number;
  starterPacks: number;
  labeler: boolean;
  chat: {
    allowIncoming: string; // e.g. "all"
  };
}

/**
 * "viewer" object, which appears on creator, subject, etc.
 */
export interface IViewer {
  muted?: boolean;
  mutedByList?: IViewerList;
  blockedBy?: boolean;
  blocking?: string;
  blockingByList?: IViewerList;
  following?: string;
  followedBy?: string;
  knownFollowers?: {
    count: number;
    followers: Array<IKnownFollower | null>;
  };
}

/**
 * "mutedByList" and "blockingByList" share this structure
 */
export interface IViewerList {
  uri: string;
  cid: string;
  name: string;
  purpose: string;
  avatar?: string;
  listItemCount: number;
  labels: ILabel[];
  viewer: {
    muted: boolean;
    blocked: string;
  };
  indexedAt: string; // date-time
}

/**
 * Each item in "knownFollowers.followers"
 */
export interface IKnownFollower {
  did: string;
  handle: string;
  displayName: string;
  avatar?: string;
  associated: IAssociated;
  labels: ILabel[];
  createdAt: string; // date-time
}

interface StarterPackProps {
  uri: string;
}

export default function StarterPack({ uri }: StarterPackProps) {
  const [postData, setPostData] = useState<IStarterPackRoot | null>(null);
  const [error, setError] = useState<Error | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);

  useEffect(() => {
    setIsLoading(true);
    setError(null);

    // Example endpoint (or your starter-pack endpoint):
    // e.g. /wp-json/rrze-bluesky/v1/starter-pack?starterPack=...
    const path = `/rrze-bluesky/v1/starter-pack?starterPack=${encodeURIComponent(
      uri,
    )}`;

    apiFetch({ path })
      .then((response: IStarterPackRoot) => {
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
    return <p>Loading starter pack data...</p>;
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
              <h2 style={{ fontSize: "2rem" }}>
                {__("How to login and connect to the Bsky API", "rrze-bluesky")}
              </h2>
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
    return <p>No starter pack data found.</p>;
  }

  // Access the starterPack object
  const { starterPack } = postData;

  // Optionally handle if there's no listItemsSample or it's empty
  if (
    !starterPack.listItemsSample ||
    starterPack.listItemsSample.length === 0
  ) {
    return <p>No items in the starter pack list.</p>;
  }

  // Show the list name plus a few samples
  return (
    <div>
      <h2>
        {__("Starter Pack:", "rrze-bluesky")} {starterPack.list.name}
      </h2>
      <p>
        {__("Total items in list:", "rrze-bluesky")}{" "}
        {starterPack.list.listItemCount}
      </p>

      <ul>
        {starterPack.listItemsSample.slice(0, 10).map((item) => (
          <li key={item.uri}>
            {/* Show displayName + handle, for example */}
            <strong>{item.subject.displayName}</strong>
            <em>@{item.subject.handle}</em>
          </li>
        ))}
      </ul>
    </div>
  );
}
