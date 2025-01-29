// ListInterfaces.ts (example filename)

export interface IListResponse {
    cursor: string;
    list: IListData;
    items: IListItem[];
  }
  
  /**
   * The "list" object that contains metadata and the "creator" info.
   */
  export interface IListData {
    uri: string;
    cid: string;
    creator: ICreator;
    name: string;
    purpose: string;
    description?: string;
    descriptionFacets?: IDescriptionFacet[];
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
   * The top-level "creator" object
   */
  export interface ICreator {
    did: string;
    handle: string;
    displayName: string;
    description?: string;
    avatar?: string;
    associated: IAssociated;
    indexedAt: string; // date-time
    createdAt: string; // date-time
    viewer: IViewer;
    labels: ILabel[];
  }
  
  /**
   * Each item in the "items" array
   */
  export interface IListItem {
    uri: string;
    subject: ISubject;
  }
  
  /**
   * The "subject" object
   */
  export interface ISubject {
    did: string;
    handle: string;
    displayName: string;
    description?: string;
    avatar?: string;
    associated: IAssociated;
    indexedAt: string; // date-time
    createdAt: string; // date-time
    viewer: IViewer;
    labels: ILabel[];
  }
  
  /**
   * "descriptionFacets" array
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
   * "associated" object
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
   * "viewer" object, which appears in "creator" or "subject"
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
      followers: Array<IKnownFollower>;
    };
  }
  
  /**
   * "mutedByList" / "blockingByList" share this structure
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
  