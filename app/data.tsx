import React from 'react';
import { __ } from '@wordpress/i18n';
import type { Field, Option } from '@wordpress/dataviews';
import { getSettings, humanTimeDiff, dateI18n, getDate } from '@wordpress/date';
import type {
	Task,
	PaginationInfo,
	TaskArgs,
	AjaxTasksResponse,
} from './types';
import apiFetch from '@wordpress/api-fetch';

/**
 * Returns the fields for the Shepherd table.
 *
 * @param data
 * @since TBD
 *
 * @return Field< any >[] The fields.
 */
export const getFields = ( data: Task[] ): Field< any >[] => {
	return [
		{
			id: 'id',
			label: __( 'Task ID', 'stellarwp-pigeon' ),
			enableHiding: false,
			enableSorting: true,
		},
		{
			id: 'action_id',
			label: __( 'Action ID', 'stellarwp-pigeon' ),
			enableHiding: false,
			enableSorting: false,
		},
		{
			id: 'task_type',
			label: __( 'Task Type', 'stellarwp-pigeon' ),
			enableHiding: true,
			enableSorting: true,
			filterBy: {
				operators: [ 'is' ],
				isPrimary: true,
			},
			elements: getUniqueValuesOfData( 'task_class', data ),
			getValue: ( { item } ) => {
				return item.data.task_class;
			},
		},
		{
			id: 'task_args',
			label: __( 'Arguments', 'stellarwp-pigeon' ),
			enableHiding: true,
			enableSorting: false,
			getValue: ( { item } ) => {
				return JSON.stringify( item.data.args, null, 4 );
			},
			render: ( { item } ) => {
				return (
					<code
						style={ {
							whiteSpace: 'pre-wrap',
							wordWrap: 'break-word',
							maxWidth: '400px',
							maxHeight: '200px',
							overflow: 'scroll',
						} }
					>
						{ item.data.args }
					</code>
				);
			},
		},
		{
			id: 'current_try',
			label: __( 'Current Try', 'stellarwp-pigeon' ),
			enableHiding: false,
			enableSorting: true,
		},
		{
			id: 'status',
			label: __( 'Status', 'stellarwp-pigeon' ),
			enableHiding: false,
			enableSorting: true,
			filterBy: {
				operators: [ 'is', 'isNot' ],
				isPrimary: true,
			},
			getValue: ( { item } ) => {
				return item.status;
			},
			elements: [
				{
					value: 'pending',
					label: __( 'Pending', 'stellarwp-pigeon' ),
				},
				{
					value: 'in-progress',
					label: __( 'In Progress', 'stellarwp-pigeon' ),
				},
				{
					value: 'complete',
					label: __( 'Complete', 'stellarwp-pigeon' ),
				},
				{
					value: 'failed',
					label: __( 'Failed', 'stellarwp-pigeon' ),
				},
				{
					value: 'cancelled',
					label: __( 'Cancelled', 'stellarwp-pigeon' ),
				},
			],
		},
		{
			id: 'scheduled_at',
			label: __( 'Scheduled At', 'stellarwp-pigeon' ),
			enableHiding: true,
			enableSorting: true,
			getValue: ( { item } ) => {
				return item.scheduled_at ?? null;
			},
			render: ( { item } ) => {
				if ( ! item.scheduled_at ) {
					return <span>{ __( 'Never', 'stellarwp-pigeon' ) }</span>;
				}

				return (
					<time
						dateTime={ item.scheduled_at.toISOString() }
						title={ dateI18n(
							getSettings().formats.datetime,
							item.scheduled_at
						) }
					>
						{ Math.abs(
							item.scheduled_at.getTime() -
								getDate( null ).getTime()
						) <
						1000 * 60 * 60 * 24
							? humanTimeDiff(
									item.scheduled_at,
									getDate( null )
							  )
							: dateI18n(
									getSettings().formats.date,
									item.scheduled_at
							  ) }
					</time>
				);
			},
		},
	];
};

/**
 * The default arguments for the tasks query.
 *
 * @since TBD
 *
 * @member TaskArgs
 */
const defaultArgs = window?.shepherdData?.defaultArgs ?? {};

const defaultArgsHash = JSON.stringify( defaultArgs );

export const getTasks = async (
	args: TaskArgs
): Promise< { data: Task[]; paginationInfo: PaginationInfo } > => {
	const argsHash = JSON.stringify( args );

	if ( argsHash === defaultArgsHash ) {
		const tasks = window?.shepherdData?.tasks;

		if ( ! tasks ) {
			return {
				data: [],
				paginationInfo: {
					totalItems: 0,
					totalPages: 0,
				},
			};
		}

		return {
			data: tasks.map( ( task ) => {
				return {
					id: task.id,
					action_id: task.action_id,
					data: task.data,
					current_try: task.current_try,
					status: task.status,
					scheduled_at: task.scheduled_at?.date
						? getDate( task.scheduled_at.date )
						: null,
					logs: task.logs,
				};
			} ),
			paginationInfo: getPaginationInfo(),
		};
	}

	const url = new URLSearchParams();
	url.append( 'action', 'shepherd_get_tasks' );
	url.append( 'nonce', window?.shepherdData?.nonce ?? '' );
	for ( const [ key, value ] of Object.entries( args ) ) {
		if ( Array.isArray( value ) ) {
			for ( const item of value ) {
				url.append( key, JSON.stringify( item ) );
			}
		} else {
			url.append( key, value.toString() );
		}
	}

	try {
		const response = await apiFetch< AjaxTasksResponse >( {
			url: window.ajaxurl,
			body: url,
			method: 'POST',
		} );

		if ( ! response.success ) {
			// eslint-disable-next-line no-console
			console.error( response );

			return {
				data: [],
				paginationInfo: {
					totalItems: 0,
					totalPages: 0,
				},
			};
		}

		return {
			data: response.data.tasks.map( ( task ) => {
				return {
					id: task.id,
					action_id: task.action_id,
					data: task.data,
					current_try: task.current_try,
					status: task.status,
					scheduled_at: task.scheduled_at?.date
						? getDate( task.scheduled_at.date )
						: null,
					logs: task.logs,
				};
			} ),
			paginationInfo: {
				totalItems: response.data.totalItems,
				totalPages: response.data.totalPages,
			},
		};
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( error );

		return {
			data: [],
			paginationInfo: {
				totalItems: 0,
				totalPages: 0,
			},
		};
	}
};

export const getPaginationInfo = (): PaginationInfo => {
	const totalItems = window?.shepherdData?.totalItems ?? 0;
	const totalPages = window?.shepherdData?.totalPages ?? 0;

	return {
		totalItems,
		totalPages,
	};
};

const uniqueValues = {};

export const getUniqueValuesOfData = (
	field: string,
	data: Task[]
): Option[] => {
	const values = data.map( ( item ) => item[ field ] ?? item.data[ field ] );

	if ( ! uniqueValues[ field ] ) {
		uniqueValues[ field ] = [];
	}

	uniqueValues[ field ] = [
		...new Set( [ ...uniqueValues[ field ], ...values ] ),
	];

	return uniqueValues[ field ].map( ( value ) => {
		return {
			label: value,
			value,
		};
	} );
};
