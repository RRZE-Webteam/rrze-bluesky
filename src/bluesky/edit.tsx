// Imports from WordPress libraries
import {
  useBlockProps,
  InspectorControls,
  BlockControls,
} from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
// import PublicTimeline from "./PublicTimeline";
import Post from "./Post";
//import player scss
import {
  __experimentalInputControl as InputControl,
  PanelBody,
  ToolbarGroup,
  ToolbarItem,
  ToolbarDropdownMenu,
} from "@wordpress/components";
import "./player.scss";

import { sanitizeUrl } from "./utils";

export interface BskyFeed {
  feed: Array<{
    post: BskyPost;
  }>;
}

interface BskyBlock {
  attributes: {
    postUrl: string;
    caching: number;
  };
  setAttributes: (attributes: { postUrl?: string; caching?: number }) => void;
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
export default function Edit({ attributes, setAttributes }: BskyBlock) {
  const { postUrl, caching } = attributes;
  const blockProps = useBlockProps();

  const onChangeUrl = (url: string) => {
    setAttributes({ postUrl: sanitizeUrl(url) });
  };

  const onChangeCaching = (caching: number) => {
    setAttributes({ caching });
  };

  const cachingOptions = [
    {
      title: "1 min",
      isDisabled: caching === 60,
      onClick: () => onChangeCaching(60),
    },
    {
      title: "5 min",
      isDisabled: caching === 300,
      onClick: () => onChangeCaching(300),
    },
    {
      title: "30 min",
      isDisabled: caching === 1800,
      onClick: () => onChangeCaching(1800),
    },
    {
      title: "1 hour",
      isDisabled: caching === 3600,
      onClick: () => onChangeCaching(3600),
    },
    {
      title: "4 hours",
      isDisabled: caching === 14400,
      onClick: () => onChangeCaching(14400),
    },
  ];

  return (
    <div {...blockProps}>
      <InspectorControls>
        <PanelBody title={__("Post Settings", "bluesky")}>
          <InputControl
            label={__("Post URL", "bluesky")}
            value={postUrl}
            onChange={onChangeUrl}
          />
        </PanelBody>
      </InspectorControls>
      <BlockControls>
        <ToolbarGroup>
          <ToolbarItem>
            {() => (
              <ToolbarDropdownMenu
                icon="clock"
                label="Caching"
                controls={cachingOptions}
              />
            )}
          </ToolbarItem>
        </ToolbarGroup>
      </BlockControls>
      <Post uri={postUrl} />
    </div>
  );
}
