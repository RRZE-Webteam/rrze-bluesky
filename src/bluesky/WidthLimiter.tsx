import { __ } from "@wordpress/i18n";
import {
  ToolbarDropdownMenu,
  ToolbarItem,
  ToolbarGroup,
  SVG,
  Path,
} from "@wordpress/components";

type WidthLimiterTogglerProps = {
  attributes: {
    widthLimiter: boolean;
  };
  setAttributes: (newAttributes: { widthLimiter: boolean }) => void;
};

/**
 * WidthLimiterBar component - Displays a toolbar for toggling X-ray feature.
 * @param props - React props.
 * @returns     - The rendered WidthLimiterBar component.
 */
const WidthLimiterBar = ({
  attributes,
  setAttributes,
}: WidthLimiterTogglerProps) => {
  const { widthLimiter } = attributes;

  /**
   * Creates an SVG icon for the WidthLimiter feature.
   * @param fillColor - The color to fill the SVG path with.
   * @returns         - The SVG icon.
   */
  const createWidthLimiterIcon = (fillColor = "none") => (
    <SVG xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
      {/*<!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->*/}
      <Path
        fill={fillColor}
        d="M256 0c17.7 0 32 14.3 32 32l0 32 32 0c12.9 0 24.6 7.8 29.6 19.8s2.2 25.7-6.9 34.9l-64 64c-12.5 12.5-32.8 12.5-45.3 0l-64-64c-9.2-9.2-11.9-22.9-6.9-34.9s16.6-19.8 29.6-19.8l32 0 0-32c0-17.7 14.3-32 32-32zM169.4 393.4l64-64c12.5-12.5 32.8-12.5 45.3 0l64 64c9.2 9.2 11.9 22.9 6.9 34.9s-16.6 19.8-29.6 19.8l-32 0 0 32c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-32-32 0c-12.9 0-24.6-7.8-29.6-19.8s-2.2-25.7 6.9-34.9zM32 224l32 0 0-32c0-12.9 7.8-24.6 19.8-29.6s25.7-2.2 34.9 6.9l64 64c12.5 12.5 12.5 32.8 0 45.3l-64 64c-9.2 9.2-22.9 11.9-34.9 6.9s-19.8-16.6-19.8-29.6l0-32-32 0c-17.7 0-32-14.3-32-32s14.3-32 32-32zm297.4 54.6c-12.5-12.5-12.5-32.8 0-45.3l64-64c9.2-9.2 22.9-11.9 34.9-6.9s19.8 16.6 19.8 29.6l0 32 32 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-32 0 0 32c0 12.9-7.8 24.6-19.8 29.6s-25.7 2.2-34.9-6.9l-64-64zM256 224a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"
      />
    </SVG>
  );

  // Define inactive and active WidthLimiter icons
  const inactiveWidthLimiterIcon = createWidthLimiterIcon("#D3D3D3");
  const ActiveWidthLimiterIcon = createWidthLimiterIcon("#000");

  /**
   * Toggles the WidthLimiter attribute.
   * @param newWidthLimiter - The new WidthLimiter state.
   */
  const toggleWidthLimiter = (newWidthLimiter: boolean) => {
    setAttributes({ widthLimiter: newWidthLimiter });
  };

  // Return the main component
  return (
    <ToolbarGroup>
      <ToolbarItem>
        {() => (
          <ToolbarDropdownMenu
            icon={
              widthLimiter ? ActiveWidthLimiterIcon : inactiveWidthLimiterIcon
            }
            label={__(
              "Display options for the Editor",
              "rrze-elements-bluesky",
            )}
            controls={[
              {
                title: __(
                  "Limit the width to 75 characters",
                  "rrze-elements-bluesky",
                ),
                icon: ActiveWidthLimiterIcon,
                onClick: () => toggleWidthLimiter(true),
              },
              {
                title: __("Do not limit the width", "rrze-elements-bluesky"),
                icon: inactiveWidthLimiterIcon,
                onClick: () => toggleWidthLimiter(false),
              },
            ]}
          />
        )}
      </ToolbarItem>
    </ToolbarGroup>
  );
};

export { WidthLimiterBar };
