export interface Link {
    label: string;
    title?: string;
    url?: string;
    href?: string;
    keyshortcuts?: string;
    requiredPermissionToDisplay?: string[];
    requiredPermissionToDisplaySilently?: string[];
    requiredPermissionToHideSilently?: string[];
    mustBeConnectedToDisplay?: any;
    mustBeDisconnectedToDisplay?: any;
    childs?: Link[];
}
