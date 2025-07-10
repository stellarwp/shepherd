import React from 'react';
import { DataViews } from '@wordpress/dataviews/wp' ;
import { Icon, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { details, edit } from '@wordpress/icons';

import { getFields, getTasks, getPaginationInfo } from '../data';


export const ShepherdTable = (arg1, arg2): React.ReactNode => {
	const onChangeView = (...args): void => {
		console.log( 'View changed', args );
	};

	const data = getTasks( 1, 10 );

	const fields = getFields();

	// const fields = [
	// 	{
	// 		id: 'title',
	// 		label: 'Title',
	// 		enableHiding: false,
	// 	},
	// 	{
	// 		id: 'date',
	// 		label: 'Date',
	// 		render: ( { item } ) => {
	// 			return <time>{ item.date }</time>;
	// 		},
	// 	},
	// 	{
	// 		id: 'author',
	// 		label: 'Author',
	// 		render: ( { item } ) => {
	// 			return <a href="...">{ item.author }</a>;
	// 		},
	// 		elements: [
	// 			{ value: 1, label: 'Admin' },
	// 			{ value: 2, label: 'User' },
	// 		],
	// 		filterBy: {
	// 			operators: [ 'is', 'isNot' ],
	// 		},
	// 		enableSorting: false,
	// 	},
	// 	{
	// 		id: 'status',
	// 		label: 'Status',
	// 		getValue: ( { item } ) =>
	// 			STATUSES.find( ( { value } ) => value === item.status )?.label ??
	// 			item.status,
	// 		elements: STATUSES,
	// 		filterBy: {
	// 			operators: [ 'isAny' ],
	// 		},
	// 		enableSorting: false,
	// 	},
	// ];

	const view = {
		type: 'table',
		search: '',
		// filters: [
		// 	{ field: 'author', operator: 'is', value: 2 },
		// 	{ field: 'status', operator: 'isAny', value: [ 'publish', 'draft' ] },
		// ],
		page: 1,
		perPage: 10,
		sort: {
			field: 'id',
			direction: 'desc',
		},
		titleField: 'id',
		fields: [ 'action_id', 'task_type', 'current_try', 'status', 'scheduled_at' ],
		layout: {},
	};

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
			supportsBulk: true,
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
					<p>Are you sure you want to delete { items.length } item(s)?</p>
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
	const paginationInfo = getPaginationInfo();

	return (
		<DataViews
			data={ data }
			fields={ fields }
			view={ view }
			onChangeView={ onChangeView }
			defaultLayouts={ defaultLayouts }
			actions={ actions }
			paginationInfo={ paginationInfo }
		/>
	);
};
