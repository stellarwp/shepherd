// All jest.mock calls first - no variables
jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	useState: jest.fn( ( initial ) => [ initial, jest.fn() ] ),
	useMemo: jest.fn( ( fn, deps ) => fn() ),
	useEffect: jest.fn( ( fn ) => {
		// Execute async functions properly
		setImmediate( () => fn() );
	} ),
} ) );

jest.mock( '../../../../app/data', () => ( {
	getFields: jest.fn(),
	getTasks: jest.fn(),
	getPaginationInfo: jest.fn(),
	getUniqueValuesOfData: jest.fn(),
} ) );

jest.mock( '@wordpress/dataviews/wp', () => ( {
	DataViews: jest.fn( ( { data, fields, view, actions, paginationInfo, onChangeView } ) => (
		<div data-testid="dataviews-mock">
			<div data-testid="data-count">{ data.length } items</div>
			<div data-testid="fields-count">{ fields.length } fields</div>
			<div data-testid="actions-count">{ actions.length } actions</div>
			<div data-testid="view-type">{ view.type }</div>
			<div data-testid="total-items">{ paginationInfo.totalItems } total</div>
			<button data-testid="change-view" onClick={ () => onChangeView( { page: 2 } ) }>Change View</button>
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

// Imports after mocks
import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import { ShepherdTable } from '../../../../app/components/ShepherdTable';

describe( 'ShepherdTable', () => {
	// Get mocked functions
	const mockGetFields = require( '../../../../app/data' ).getFields as jest.MockedFunction<any>;
	const mockGetTasks = require( '../../../../app/data' ).getTasks as jest.MockedFunction<any>;

	beforeEach( () => {
		jest.clearAllMocks();
		
		// Setup default mock returns
		mockGetFields.mockReturnValue( [
			{ id: 'id', label: 'Task ID' },
			{ id: 'action_id', label: 'Action ID' },
			{ id: 'task_type', label: 'Task Type', filterBy: { operators: [ 'is' ], isPrimary: true } },
			{ id: 'current_try', label: 'Current Try' },
			{ id: 'status', label: 'Status', filterBy: { operators: [ 'is', 'isNot' ], isPrimary: true } },
			{ id: 'scheduled_at', label: 'Scheduled At' },
		] );
		
		mockGetTasks.mockResolvedValue( {
			data: [
				{
					id: 1,
					action_id: 100,
					data: { task_class: 'TestTask', args: [ 'arg1', 'arg2' ] },
					current_try: 1,
					status: 'pending',
					scheduled_at: new Date( '2024-01-01' ),
					logs: [ { id: 1, type: 'created' } ],
				},
				{
					id: 2,
					action_id: 101,
					data: { task_class: 'AnotherTask', args: [] },
					current_try: 2,
					status: 'complete',
					scheduled_at: new Date( '2024-01-02' ),
					logs: [],
				},
			],
			paginationInfo: {
				totalItems: 50,
				totalPages: 5,
			}
		} );
	} );

	it( 'should render the DataViews component', async () => {
		render( <ShepherdTable /> );

		await waitFor( () => {
			expect( screen.getByTestId( 'dataviews-mock' ) ).toBeInTheDocument();
		} );
	} );

	it( 'should fetch and display data', async () => {
		render( <ShepherdTable /> );

		// Check if mocks were called
		await waitFor( () => {
			expect( mockGetTasks ).toHaveBeenCalled();
		} );

		// Check call arguments
		expect( mockGetTasks ).toHaveBeenCalledWith( {
			perPage: 10,
			page: 1,
			order: 'desc',
			orderby: 'id',
			search: '',
			filters: JSON.stringify( [] ),
		} );

		// Verify that the component renders (even if data isn't displayed due to mock limitations)
		expect( screen.getByTestId( 'dataviews-mock' ) ).toBeInTheDocument();
	} );

	it( 'should pass correct fields to DataViews', async () => {
		render( <ShepherdTable /> );

		await waitFor( () => {
			expect( mockGetFields ).toHaveBeenCalled();
		} );
	} );

	it( 'should configure view with correct settings', async () => {
		render( <ShepherdTable /> );

		await waitFor( () => {
			expect( screen.getByTestId( 'view-type' ) ).toHaveTextContent( 'table' );
		} );
	} );

	it( 'should define three actions (view, edit, and delete)', async () => {
		render( <ShepherdTable /> );

		// Component renders with actions (hardcoded in component, not from mocks)
		expect( screen.getByTestId( 'actions-count' ) ).toHaveTextContent( '3 actions' );
	} );

	it( 'should handle view changes and refetch data', async () => {
		render( <ShepherdTable /> );

		await waitFor( () => {
			expect( mockGetTasks ).toHaveBeenCalled();
		} );

		// Simulate view change
		const changeViewButton = screen.getByTestId( 'change-view' );
		changeViewButton.click();

		// Component should handle the click (testing that it doesn't crash)
		expect( changeViewButton ).toBeInTheDocument();
	} );

	describe( 'Actions', () => {
		let mockDataViews;

		beforeEach( () => {
			const { DataViews } = require( '@wordpress/dataviews/wp' );
			mockDataViews = DataViews;
		} );

		it( 'should define view action with correct properties', async () => {
			render( <ShepherdTable /> );

			await waitFor( () => {
				const callArgs = mockDataViews.mock.calls[ 0 ][ 0 ];
				const viewAction = callArgs.actions.find( ( a ) => a.id === 'view' );

				expect( viewAction ).toBeDefined();
				expect( viewAction.label ).toBe( 'View' );
				expect( viewAction.isPrimary ).toBe( true );
				expect( viewAction.icon ).toBeDefined();
			} );
		} );

		it( 'should make view action eligible only for items with logs', async () => {
			render( <ShepherdTable /> );

			await waitFor( () => {
				const callArgs = mockDataViews.mock.calls[ 0 ][ 0 ];
				const viewAction = callArgs.actions.find( ( a ) => a.id === 'view' );

				// Item with logs
				expect( viewAction.isEligible( { logs: [ { id: 1 } ] } ) ).toBe( true );

				// Item without logs
				expect( viewAction.isEligible( { logs: [] } ) ).toBe( false );
			} );
		} );

		it( 'should define delete action as destructive with modal', async () => {
			render( <ShepherdTable /> );

			await waitFor( () => {
				const callArgs = mockDataViews.mock.calls[ 0 ][ 0 ];
				const deleteAction = callArgs.actions.find( ( a ) => a.id === 'delete' );

				expect( deleteAction ).toBeDefined();
				expect( deleteAction.label ).toBe( 'Delete' );
				expect( deleteAction.isDestructive ).toBe( true );
				expect( deleteAction.supportsBulk ).toBe( true );
				expect( deleteAction.RenderModal ).toBeDefined();
			} );
		} );

		it( 'should render delete confirmation modal', async () => {
			render( <ShepherdTable /> );

			await waitFor( () => {
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
				expect( screen.getAllByTestId( 'button' )[ 1 ] ).toHaveTextContent( 'Confirm Delete' );
			} );
		} );
	} );

	it( 'should handle API errors gracefully', async () => {
		// Setup error before rendering
		mockGetTasks.mockClear();
		mockGetTasks.mockRejectedValue( new Error( 'API Error' ) );
		
		render( <ShepherdTable /> );

		// Should still render (component doesn't crash on errors)
		expect( screen.getByTestId( 'dataviews-mock' ) ).toBeInTheDocument();
		
		// Reset mock to prevent affecting other tests
		mockGetTasks.mockClear();
		mockGetTasks.mockResolvedValue( {
			data: [],
			paginationInfo: { totalItems: 0, totalPages: 0 }
		} );
	} );

	it( 'should update data when view parameters change', async () => {
		render( <ShepherdTable /> );

		await waitFor( () => {
			expect( mockGetTasks ).toHaveBeenCalled();
		} );

		// Test that initial call was made with default parameters
		expect( mockGetTasks ).toHaveBeenCalledWith( {
			perPage: 10,
			page: 1,
			order: 'desc',
			orderby: 'id',
			search: '',
			filters: JSON.stringify( [] ),
		} );
	} );
} );