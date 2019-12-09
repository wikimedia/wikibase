export enum PermissionErrorType {
	PROTECTED_PAGE = 1,
	CASCADE_PROTECTED_PAGE,
	UNKNOWN = -1,
}

export type PermissionError = PermissionErrorProtectedPage |
PermissionErrorCascadeProtectedPage |
PermissionErrorUnknown;

export interface PermissionErrorProtectedPage {
	type: PermissionErrorType.PROTECTED_PAGE;
	right: 'editprotected' | 'editsemiprotected' | string;
	semiProtected: boolean;
}

export interface PermissionErrorCascadeProtectedPage {
	type: PermissionErrorType.CASCADE_PROTECTED_PAGE;
	pages: string[];
}

export interface PermissionErrorUnknown {
	type: PermissionErrorType.UNKNOWN;
	code: string;
	messageKey: string;
	messageParams: ( string|number )[];
}

/**
 * A repository for determining potential permission errors
 * when editing a page.
 */
export default interface PageEditPermissionErrorsRepository {
	/**
	 * Determine which permission error(s) prevent a user from editing
	 * the page with the given title, if any.
	 * If the resulting array is empty, the user can edit the page.
	 * @param title The page title, possibly including a namespace.
	 */
	getPermissionErrors( title: string ): Promise<PermissionError[]>;
}
