import React from 'react';
import { __ } from '@wordpress/i18n';
import type { Field, PaginationInfo } from '@wordpress/dataviews';
import { getSettings, humanTimeDiff, dateI18n, getDate } from '@wordpress/date';
import type { Task } from './types';

/**
 * Returns the fields for the Shepherd table.
 *
 * @since TBD
 *
 * @return Field< any >[] The fields.
 */
export const getFields = (): Field< any >[] => {
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
				return <code style={ { whiteSpace: 'pre-wrap', wordWrap: 'break-word', maxWidth: '400px', maxHeight: '200px', overflow: 'scroll' } }>{ item.data.args }</code>;
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
			getValue: ( { item } ) => {
				return item.status.slug;
			},
			elements: [
				{
					value: 'pending',
					label: __( 'Pending', 'stellarwp-pigeon' ),
				},
				{
					value: 'running',
					label: __( 'Running', 'stellarwp-pigeon' ),
				},
				{
					value: 'success',
					label: __( 'Success', 'stellarwp-pigeon' ),
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
						title={ dateI18n( getSettings().formats.datetime, item.scheduled_at ) }
					>
						{
							Math.abs( item.scheduled_at.getTime() - getDate( null ).getTime() ) < 1000 * 60 * 60 * 24
								? humanTimeDiff( item.scheduled_at, getDate( null ) )
								: dateI18n( getSettings().formats.date, item.scheduled_at )
						}
					</time>
				);
			},
		},
	];
};

export const getTasks = ( $page: number, $per_page: number ): Task[] => {
	const tasks = window?.shepherdData?.tasks;

	if ( ! tasks ) {
		return [];
	}

	return tasks.map( ( task ) => {
		return {
			id: task.id,
			action_id: task.action_id,
			data: task.data,
			current_try: task.current_try,
			status: task.status,
			scheduled_at: task.scheduled_at?.date ? getDate( task.scheduled_at.date ) : null,
			logs: task.logs,
		};
	} );
};

export const getPaginationInfo = (): PaginationInfo => {
	const totalItems = window?.shepherdData?.totalItems;
	const totalPages = window?.shepherdData?.totalPages;

	return {
		totalItems,
		totalPages,
	};
}
