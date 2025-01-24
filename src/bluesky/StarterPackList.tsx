// StarterPackList.tsx
import { useEffect, useState } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { __ } from "@wordpress/i18n";
import { Notice } from "@wordpress/components";

// Import the interfaces from above (or paste them in directly):
import { IListResponse, IListItem } from "./types"; // Adjust path as needed

interface ListProps {
  // The AT-URI or bsky link for the list.
  // If your REST route also supports `starterPack` param,
  // you could rename this to something else (like `param`)
  // and construct the query string differently.
  listUri: string;
}

/**
 * A React component that loads & displays a Bluesky List
 * from the "rrze-bluesky/v1/list" endpoint.
 */
export default function StarterPackList({ listUri }: ListProps) {
  const [data, setData] = useState<IListResponse | null>(null);
  const [error, setError] = useState<Error | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);

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
      <h2>{list.name}</h2>
      {list.description && <p>{list.description}</p>}

      <ul className="bsky-starterpack-list">
        {/* Reverse items by calling .slice() then .reverse() */}
        {items
          .slice()
          .reverse()
          .map((item: IListItem) => (
            <li className="bsky-starterpack-list-item" key={item.uri}>
              <strong>{item.subject.displayName}</strong> (
              <em>@{item.subject.handle}</em>)
              {item.subject.description && <p>{item.subject.description}</p>}
            </li>
          ))}
      </ul>
    </div>
  );
}
