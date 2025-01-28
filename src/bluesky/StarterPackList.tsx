// StarterPackList.tsx
import { useEffect, useState } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { __ } from "@wordpress/i18n";
import { Notice } from "@wordpress/components";
import HeadingComponent from "./HeadingComponent";

// Import the interfaces from above (or paste them in directly):
import { IListResponse, IListItem } from "./types"; // Adjust path as needed

interface ListProps {
  // The AT-URI or bsky link for the list.
  // If your REST route also supports `starterPack` param,
  // you could rename this to something else (like `param`)
  // and construct the query string differently.
  listUri: string;
  hstart: number;
}

/**
 * A React component that loads & displays a Bluesky List
 * from the "rrze-bluesky/v1/list" endpoint.
 */
export default function StarterPackList({ listUri, hstart }: ListProps) {
  const [data, setData] = useState<IListResponse | null>(null);
  const [error, setError] = useState<Error | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isNoticeVisible, setIsNoticeVisible] = useState<boolean>(true);

  useEffect(() => {
    setIsLoading(true);
    setError(null);

    const isAtUri = listUri.startsWith("at://");
    const paramName = isAtUri ? "list" : "starterPack";
    console.log(listUri);

    const path = `/rrze-bluesky/v1/list?${paramName}=${encodeURIComponent(
      listUri,
    )}`;

    apiFetch({ path })
      .then((response: IListResponse) => {
        console.log(response);
        setData(response);
      })
      .catch((err: Error) => {
        setError(err);
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, [listUri]);

  if (isLoading) {
    return <p>{__("Loading list data...", "rrze-bluesky")}</p>;
  }

  if (error) {
    return (
      <Notice status="error" isDismissible={false}>
        {__("Error:", "rrze-bluesky")} {error.message}
      </Notice>
    );
  }

  if (!data) {
    return <p>{__("No list data found.", "rrze-bluesky")}</p>;
  }

  // Destructure the "list" from the data
  const { list, items } = data;

  // For safety, check if "items" is empty
  if (!items || items.length === 0) {
    return <p>{__("The list is empty.", "rrze-bluesky")}</p>;
  }

  return (
    <div className="bluesky-list-block">
      <HeadingComponent
        level={hstart}
      >{list.name}</HeadingComponent>
      {list.description && <p>{list.description}</p>}

      {isNoticeVisible && (
        <Notice
          status="info"
          isDismissible={true}
          onDismiss={() => setIsNoticeVisible(false)}
        >
          {__(
            "This is a preview of the first 50 entries. The frontend will show the complete StarterPack with up to 150 entries.",
            "rrze-bluesky",
          )}
        </Notice>
      )}
      <ul className="bsky-starterpack-list">
        {/* Reverse items by calling .slice() then .reverse() */}
        {items
          .slice()
          .reverse()
          .map((item: IListItem) => (
            <li className="bsky-starterpack-list-item" key={item.uri}>
              <div className="bsky-profile">
                {item.subject.avatar && (
                  <img
                    className="bsky-avatar"
                    src={item.subject.avatar}
                    alt={item.subject.displayName}
                  />
                )}
                <div className="bsky-social-link">
                  <strong>{item.subject.displayName}</strong>
                  <em>@{item.subject.handle}</em>
                </div>
                <a
                  href={"https://bsky.app/profile/" + item.subject.did}
                  aria-label={"Follow " + item.subject.displayName}
                  className="bsky-follow-button"
                >
                  <svg
                    className="bsky-svg"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 448 512"
                  >
                    <path
                      fill="currentColor"
                      d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 144L48 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l144 0 0 144c0 17.7 14.3 32 32 32s32-14.3 32-32l0-144 144 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-144 0 0-144z"
                    />
                  </svg>
                  {__("Follow", "rrze-bluesky")}
                </a>
              </div>
              {item.subject.description && <p>{item.subject.description}</p>}
            </li>
          ))}
      </ul>
    </div>
  );
}
