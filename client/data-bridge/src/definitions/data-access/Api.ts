import { ApiQueryResponse } from '@/definitions/data-access/ApiQuery';
import { ApiWbgetentitiesResponse } from '@/definitions/data-access/ApiWbgetentities';

/**
 * An interface for MediaWiki API requests.
 * Some implementations may merge compatible requests for efficiency.
 *
 * Usage example:
 *
 * ```
 * api.get( {
 *     action: 'query',
 *     prop: new Set( [ 'info' ] ),
 *     meta: new Set( [ 'siteinfo' ] ),
 *     titles: new Set( [ 'Help:Contents', 'Project:Main Page' ] ),
 *     redirects: true,
 *     inprop: new Set( [ 'url' ] ),
 *     siprop: new Set( [ 'usergroups' ] ),
 *     formatversion: 2,
 * } );
 * ```
 *
 * Each parameter value may be:
 *
 * - A string or integer, for a plain parameter that must be exactly this value.
 * - A boolean, which is completely omitted if false.
 * - A Set of strings or integers, which may be merged with other sets from compatible requests.
 *   (Integers are interchangeable with their string representations.)
 *   This should be used for most multi-value parameters,
 *   replacing most uses of arrays with MediaWiki’s API class.
 * - An Array of strings or integers, which will never be merged with other requests.
 *   This is mainly useful for parameters that allow duplicate values,
 *   or where the order is significant.
 *
 * Callers should specify all the parameters that they rely on,
 * even where this means specifying the default value, so that
 * conflicts with requests specifying non-default values can be detected.
 * Using formatversion: 2 is strongly encouraged.
 */
export default interface Api {
	/**
	 * Send a GET request with at least the given parameters.
	 * The resulting response may include data from other requests
	 * which were combined with this one.
	 */
	get<action extends ApiAction>( params: ApiParams<action> ): Promise<ApiResponsesMap[ action ]>;
}

export interface ApiParams<action extends ApiAction> {
	action: action;
	[ name: string ]: string | number | boolean | ( string|number )[] | Set<string|number>;
}

export type ApiResponse = {
	// there are no members common to all API modules
};

export interface ApiResponsesMap {
	query: ApiQueryResponse;
	wbgetentities: ApiWbgetentitiesResponse;
	[ action: string ]: ApiResponse;
}
export type ApiAction = keyof ApiResponsesMap & string;

export interface ApiError {
	code: string;
	data?: object;
}
