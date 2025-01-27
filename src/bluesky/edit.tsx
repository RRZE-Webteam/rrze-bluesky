import {
  useBlockProps,
  InspectorControls,
  BlockControls,
} from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import {
  __experimentalInputControl as InputControl,
  PanelBody,
  ToolbarGroup,
  ToolbarItem,
  ToolbarDropdownMenu,
  Notice,
} from "@wordpress/components";
import "./player.scss";
import { sanitizeUrl } from "./utils";
import Post from "./Post";
import StarterPackList from "./StarterPackList";

// Helper function to determine URL type
const getUrlType = (url: string): "post" | "starterPack" | "unknown" => {
  if (/https:\/\/bsky\.app\/profile\/.+\/post\/.+/.test(url)) {
    return "post";
  }
  if (/https:\/\/bsky\.app\/starter-pack\/.+\/.+/.test(url)) {
    return "starterPack";
  }
  return "unknown";
};

interface BskyBlock {
  attributes: {
    postUrl: string;
    caching: number;
    isPost: boolean;
    isStarterPack: boolean;
  };
  setAttributes: (attributes: { postUrl?: string; caching?: number; isPost?: boolean; isStarterPack?: boolean }) => void;
}

export default function Edit({ attributes, setAttributes }: BskyBlock) {
  const { postUrl, caching } = attributes;
  const blockProps = useBlockProps();
  let urlType = getUrlType(postUrl);

  const onChangeUrl = (url: string) => {
    urlType = getUrlType(url);
    setAttributes({ postUrl: sanitizeUrl(url), isPost: urlType === "post", isStarterPack: urlType === "starterPack" });
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
            label={__("Post or StarterPack URL", "bluesky")}
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
      {urlType === "post" && <Post uri={postUrl} />}
      {urlType === "starterPack" && <StarterPackList listUri={postUrl} />}
      {urlType === "unknown" && (
        <Notice status="error" isDismissible={false}>
          {__("Invalid URL. Please enter a valid Post or StarterPack URL.", "bluesky")}
        </Notice>
      )}
    </div>
  );
}
