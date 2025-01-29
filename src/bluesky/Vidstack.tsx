///////////////////////////////
// Import WordPress Dependencies
import { useRef, memo } from "@wordpress/element";

// Import Vidstack Dependencies
import {
  MediaPlayer,
  MediaProvider,
  isYouTubeProvider,
  type MediaProviderAdapter,
  type MediaPlayerInstance,
} from "@vidstack/react";
import {
  defaultLayoutIcons,
  DefaultVideoLayout,
  DefaultAudioLayout,
} from "@vidstack/react/player/layouts/default";
import { Poster } from "@vidstack/react";

///////////////////////////////
// Interfaces
interface CustomVidStackProps {
  title: string;
  mediaurl: string;
  aspectratio: string;
  poster: string;
}
///////////////////////////////
// Custom Vidstack Player with Memo
// eslint-disable-next-line no-undef
const RRZEVidstackPlayer: React.FC<CustomVidStackProps> = memo(
  ({ title, mediaurl, aspectratio, poster }) => {
    let player = useRef<MediaPlayerInstance>(null);

    ///////////////////////////////
    // Use Effects

    ///////////////////////////////
    // Event handlers
    const handleProviderChange = (provider: MediaProviderAdapter | null) => {
      if (isYouTubeProvider(provider)) {
        provider.cookies = true;
      }
    };

    ///////////////////////////////
    // Render
    return (
      <MediaPlayer
        title={title}
        src={mediaurl}
        aspectRatio={aspectratio}
        onProviderChange={handleProviderChange}
        ref={player}
        crossOrigin
        playsInline
      >
        <MediaProvider>
          <Poster src={poster} alt="" className="vds-poster" />
        </MediaProvider>
        {/* Layouts */}
        <DefaultAudioLayout icons={defaultLayoutIcons} />
        <DefaultVideoLayout icons={defaultLayoutIcons} />
      </MediaPlayer>
    );
  },
  (prevProps, nextProps) => {
    return (
      prevProps.title === nextProps.title &&
      prevProps.mediaurl === nextProps.mediaurl &&
      prevProps.aspectratio === nextProps.aspectratio &&
      prevProps.poster === nextProps.poster
    );
  },
);

export { RRZEVidstackPlayer };
