import React from 'react';
import { DataViews } from '@wordpress/dataviews/wp';
import { Icon, Button } from '@wordpress/components';
import { details, edit } from '@wordpress/icons';
import { useState, useMemo, useEffect } from '@wordpress/element';
import type { View, Field } from '@wordpress/dataviews';
import type { Task, PaginationInfo } from '../types';

import { getFields, getTasks } from '../data';

export const ShepherdTable = (): React.ReactNode => {
	const [ view, setView ] = useState< View >( {
		type: 'table',
		search: '',
		filters: [],
		page: 1,
		perPage: 10,
		sort: {
			field: 'id',
			direction: 'desc',
		},
		titleField: 'id',
		fields: [
			'action_id',
			'task_type',
			'current_try',
			'status',
			'scheduled_at',
		],
		layout: {},
	} );

	const args = useMemo( () => {
		const filters = [];

		if ( view?.filters ) {
			view.filters.forEach( ( filter ) => {
				filter.operator
				filter.field
				filter.value
			} );
		}

		return {
			perPage: view.perPage ?? 10,
			page: view.page ?? 1,
			order: view.sort?.direction ?? 'desc',
			orderby: view.sort?.field ?? 'id',
			search: view.search ?? '',
			filters: filters,
		};
	}, [ view ] );

	const [ data, setData ] = useState< Task[] >( [] );
	const [ fields, setFields ] = useState< Field< any >[] >( [] );
	const [ paginationInfo, setPaginationInfo ] = useState< PaginationInfo >( {
		totalItems: 0,
		totalPages: 0,
	} );

	useEffect( () => {
		const promise = async (): Promise< { data: Task[]; fields: Field< any >[], paginationInfo: PaginationInfo } > => {
			const { data, paginationInfo } = await getTasks( args );
			const fields = getFields( data );

			return { data, fields, paginationInfo };
		};

		promise().then( ( args: { data: Task[]; fields: Field< any >[], paginationInfo: PaginationInfo } ): void => {
			setData( args.data );
			setFields( args.fields );
			setPaginationInfo( args.paginationInfo );
		} );
	}, [ args ] );

	const defaultLayouts = {
		table: {
			showMedia: false,
		},
	};

	const actions = [
		{
			id: 'view',
			label: 'View',
			isPrimary: true,
			icon: <Icon icon={ details } />,
			isEligible: ( item ) => item.logs.length > 0,
			callback: ( items ) => {
				console.log( 'Viewing item:', items[ 0 ] );
			},
		},
		{
			id: 'edit',
			label: 'Edit',
			icon: <Icon icon={ edit } />,
			callback: ( items ) => {
				console.log( 'Editing items:', items );
			},
		},
		{
			id: 'delete',
			label: 'Delete',
			isDestructive: true,
			supportsBulk: true,
			RenderModal: ( { items, closeModal, onActionPerformed } ) => (
				<div>
					<p>
						Are you sure you want to delete { items.length }{ ' ' }
						item(s)?
					</p>
					<Button
						variant="primary"
						onClick={ () => {
							console.log( 'Deleting items:', items );
							onActionPerformed();
							closeModal();
						} }
					>
						Confirm Delete
					</Button>
				</div>
			),
		},
	];

	return (
		<DataViews
			data={ data }
			fields={ fields }
			view={ view }
			onChangeView={ setView }
			defaultLayouts={ defaultLayouts }
			actions={ actions }
			paginationInfo={ paginationInfo }
		/>
	);
};
