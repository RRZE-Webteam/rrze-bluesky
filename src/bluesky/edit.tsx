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
  Notice,
  Placeholder,
} from "@wordpress/components";
import "./player.scss";
import { sanitizeUrl } from "./utils";
import Post from "./Post";
import StarterPackList from "./StarterPackList";
import { HeadingSelector, HeadingSelectorInspector } from "./HeadingSelector";

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
    hstart: number;
  };
  setAttributes: (attributes: {
    postUrl?: string;
    caching?: number;
    isPost?: boolean;
    isStarterPack?: boolean;
    hstart?: number;
  }) => void;
}

export default function Edit({ attributes, setAttributes }: BskyBlock) {
  const { postUrl, isStarterPack } = attributes;
  const blockProps = useBlockProps();
  let urlType = getUrlType(postUrl);

  const onChangeUrl = (url: string) => {
    urlType = getUrlType(url);
    setAttributes({
      postUrl: sanitizeUrl(url),
      isPost: urlType === "post",
      isStarterPack: urlType === "starterPack",
    });
  };

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
        {isStarterPack && (
          <PanelBody title={__("StarterPack Settings", "bluesky")}>
            <HeadingSelectorInspector
              attributes={{ hstart: attributes.hstart }}
              setAttributes={(newAttributes) => setAttributes(newAttributes)}
            />
          </PanelBody>
        )}
      </InspectorControls>
      {isStarterPack && (
        <BlockControls>
          <ToolbarGroup>
            <ToolbarItem>
              {() => (
                <HeadingSelector
                  attributes={{ hstart: attributes.hstart }}
                  setAttributes={(newAttributes) =>
                    setAttributes(newAttributes)
                  }
                />
              )}
            </ToolbarItem>
          </ToolbarGroup>
        </BlockControls>
      )}
      {(postUrl === "" || urlType === "unknown") && (
        <Placeholder
          icon="admin-post"
          label={__("Bluesky Embed", "bluesky")}
          instructions={__(
            "Enter a valid Post or StarterPack URL to display content.",
            "bluesky",
          )}
        >
          <InputControl
            label={__("Post or StarterPack URL", "bluesky")}
            value={postUrl}
            onChange={onChangeUrl}
          />
        </Placeholder>
      )}
      {urlType === "post" && <Post uri={postUrl} />}
      {urlType === "starterPack" && (
        <StarterPackList listUri={postUrl} hstart={attributes.hstart} />
      )}
      {urlType === "unknown" && (
        <Notice status="error" isDismissible={false}>
          {__(
            "Invalid URL. Please enter a valid Post or StarterPack URL.",
            "bluesky",
          )}
        </Notice>
      )}
    </div>
  );
}
