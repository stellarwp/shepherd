import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import { ShepherdTable } from '../../../../app/components/ShepherdTable';

// Mock WordPress dependencies
jest.mock( '@wordpress/dataviews/wp', () => ( {
	DataViews: jest.fn( ( { data, fields, view, actions } ) => (
		<div data-testid="dataviews-mock">
			<div data-testid="data-count">{ data.length } items</div>
			<div data-testid="fields-count">{ fields.length } fields</div>
			<div data-testid="actions-count">{ actions.length } actions</div>
			<div data-testid="view-type">{ view.type }</div>
		</div>
	) ),
} ) );

jest.mock( '@wordpress/components', () => ( {
	Icon: jest.fn( ( { icon } ) => <span data-testid="icon">Icon</span> ),
	Button: jest.fn( ( { children, onClick, variant } ) => (
		<button data-testid="button" onClick={ onClick } className={ variant }>
			{ children }
		</button>
	) ),
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: jest.fn( ( text ) => text ),
} ) );

jest.mock( '@wordpress/icons', () => ( {
	details: 'details-icon',
	edit: 'edit-icon',
} ) );

// Mock the data module
jest.mock( '../../../../app/data', () => ( {
	getFields: jest.fn( () => [
		{ id: 'id', label: 'Task ID' },
		{ id: 'action_id', label: 'Action ID' },
		{ id: 'task_type', label: 'Task Type' },
		{ id: 'current_try', label: 'Current Try' },
		{ id: 'status', label: 'Status' },
		{ id: 'scheduled_at', label: 'Scheduled At' },
	] ),
	getTasks: jest.fn( () => [
		{
			id: 1,
			action_id: 100,
			data: { task_class: 'TestTask', args: [ 'arg1', 'arg2' ] },
			current_try: 1,
			status: { slug: 'pending', label: 'Pending' },
			scheduled_at: new Date( '2024-01-01' ),
			logs: [ { id: 1, type: 'created' } ],
		},
		{
			id: 2,
			action_id: 101,
			data: { task_class: 'AnotherTask', args: [] },
			current_try: 2,
			status: { slug: 'running', label: 'Running' },
			scheduled_at: new Date( '2024-01-02' ),
			logs: [],
		},
	] ),
	getPaginationInfo: jest.fn( () => ( {
		totalItems: 50,
		totalPages: 5,
	} ) ),
} ) );

describe( 'ShepherdTable', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render the DataViews component', () => {
		render( <ShepherdTable /> );

		expect( screen.getByTestId( 'dataviews-mock' ) ).toBeInTheDocument();
	} );

	it( 'should pass correct data to DataViews', () => {
		render( <ShepherdTable /> );

		// Check that it shows 2 items (from getTasks mock)
		expect( screen.getByTestId( 'data-count' ) ).toHaveTextContent( '2 items' );
	} );

	it( 'should pass correct fields to DataViews', () => {
		render( <ShepherdTable /> );

		// Check that it shows 6 fields (from getFields mock)
		expect( screen.getByTestId( 'fields-count' ) ).toHaveTextContent( '6 fields' );
	} );

	it( 'should configure view with correct settings', () => {
		render( <ShepherdTable /> );

		// Check view type
		expect( screen.getByTestId( 'view-type' ) ).toHaveTextContent( 'table' );
	} );

	it( 'should define three actions', () => {
		render( <ShepherdTable /> );

		// Check that 3 actions are defined
		expect( screen.getByTestId( 'actions-count' ) ).toHaveTextContent( '3 actions' );
	} );

	it( 'should call getTasks with correct parameters', () => {
		const { getTasks } = require( '../../../../app/data' );

		render( <ShepherdTable /> );

		expect( getTasks ).toHaveBeenCalledWith( 1, 10 );
	} );

	it( 'should call getPaginationInfo', () => {
		const { getPaginationInfo } = require( '../../../../app/data' );

		render( <ShepherdTable /> );

		expect( getPaginationInfo ).toHaveBeenCalled();
	} );

	describe( 'Actions', () => {
		let mockDataViews;

		beforeEach( () => {
			const { DataViews } = require( '@wordpress/dataviews/wp' );
			mockDataViews = DataViews;
		} );

		it( 'should define view action with correct properties', () => {
			render( <ShepherdTable /> );

			const callArgs = mockDataViews.mock.calls[ 0 ][ 0 ];
			const viewAction = callArgs.actions.find( ( a ) => a.id === 'view' );

			expect( viewAction ).toBeDefined();
			expect( viewAction.label ).toBe( 'View' );
			expect( viewAction.isPrimary ).toBe( true );
			expect( viewAction.icon ).toBeDefined();
		} );

		it( 'should make view action eligible only for items with logs', () => {
			render( <ShepherdTable /> );

			const callArgs = mockDataViews.mock.calls[ 0 ][ 0 ];
			const viewAction = callArgs.actions.find( ( a ) => a.id === 'view' );

			// Item with logs
			expect( viewAction.isEligible( { logs: [ { id: 1 } ] } ) ).toBe( true );

			// Item without logs
			expect( viewAction.isEligible( { logs: [] } ) ).toBe( false );
		} );

		it( 'should define edit action with bulk support', () => {
			render( <ShepherdTable /> );

			const callArgs = mockDataViews.mock.calls[ 0 ][ 0 ];
			const editAction = callArgs.actions.find( ( a ) => a.id === 'edit' );

			expect( editAction ).toBeDefined();
			expect( editAction.label ).toBe( 'Edit' );
			expect( editAction.supportsBulk ).toBe( true );
		} );

		it( 'should define delete action as destructive with modal', () => {
			render( <ShepherdTable /> );

			const callArgs = mockDataViews.mock.calls[ 0 ][ 0 ];
			const deleteAction = callArgs.actions.find( ( a ) => a.id === 'delete' );

			expect( deleteAction ).toBeDefined();
			expect( deleteAction.label ).toBe( 'Delete' );
			expect( deleteAction.isDestructive ).toBe( true );
			expect( deleteAction.supportsBulk ).toBe( true );
			expect( deleteAction.RenderModal ).toBeDefined();
		} );

		it( 'should render delete confirmation modal', () => {
			render( <ShepherdTable /> );

			const callArgs = mockDataViews.mock.calls[ 0 ][ 0 ];
			const deleteAction = callArgs.actions.find( ( a ) => a.id === 'delete' );

			const mockCloseModal = jest.fn();
			const mockOnActionPerformed = jest.fn();
			const items = [ { id: 1 }, { id: 2 } ];

			const { container } = render(
				<deleteAction.RenderModal
					items={ items }
					closeModal={ mockCloseModal }
					onActionPerformed={ mockOnActionPerformed }
				/>
			);

			expect( container ).toHaveTextContent( 'Are you sure you want to delete 2 item(s)?' );
			expect( screen.getByTestId( 'button' ) ).toHaveTextContent( 'Confirm Delete' );
		} );
	} );
} );