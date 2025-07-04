import React from 'react';
import { DataViews } from '@wordpress/dataviews/wp' ;
import { Icon, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { details, edit } from '@wordpress/icons';

export const ShepherdTable = (): React.ReactNode => {
	const onChangeView = (): void => {
		/* React to user changes. */
	};

	const data = [
		{
			id: 1,
			title: 'Title',
			author: 'Admin',
			date: '2012-04-23T18:25:43.511Z',
		},
	];

	const STATUSES = [
		{ value: 'draft', label: __( 'Draft' ) },
		{ value: 'future', label: __( 'Scheduled' ) },
		{ value: 'pending', label: __( 'Pending Review' ) },
		{ value: 'private', label: __( 'Private' ) },
		{ value: 'publish', label: __( 'Published' ) },
		{ value: 'trash', label: __( 'Trash' ) },
	];
	const fields = [
		{
			id: 'title',
			label: 'Title',
			enableHiding: false,
		},
		{
			id: 'date',
			label: 'Date',
			render: ( { item } ) => {
				return <time>{ item.date }</time>;
			},
		},
		{
			id: 'author',
			label: 'Author',
			render: ( { item } ) => {
				return <a href="...">{ item.author }</a>;
			},
			elements: [
				{ value: 1, label: 'Admin' },
				{ value: 2, label: 'User' },
			],
			filterBy: {
				operators: [ 'is', 'isNot' ],
			},
			enableSorting: false,
		},
		{
			id: 'status',
			label: 'Status',
			getValue: ( { item } ) =>
				STATUSES.find( ( { value } ) => value === item.status )?.label ??
				item.status,
			elements: STATUSES,
			filterBy: {
				operators: [ 'isAny' ],
			},
			enableSorting: false,
		},
	];

	const view = {
		type: 'table',
		search: '',
		filters: [
			{ field: 'author', operator: 'is', value: 2 },
			{ field: 'status', operator: 'isAny', value: [ 'publish', 'draft' ] },
		],
		page: 1,
		perPage: 5,
		sort: {
			field: 'date',
			direction: 'desc',
		},
		titleField: 'title',
		fields: [ 'author', 'status' ],
		layout: {},
	};

	const defaultLayouts = {
		table: {
			showMedia: false,
		},
		grid: {
			showMedia: true,
		},
	};

	const actions = [
		{
			id: 'view',
			label: 'View',
			isPrimary: true,
			icon: <Icon icon={ details } />,
			isEligible: ( item ) => item.status === 'published',
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
	const paginationInfo = [];

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
