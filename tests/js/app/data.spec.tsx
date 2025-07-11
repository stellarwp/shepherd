import React from 'react';
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';
// Data functions imported per test to avoid module cache issues
import type { Field } from '@wordpress/dataviews';
import type { Task } from '../../../app/types';

// Mock WordPress dependencies
jest.mock( '@wordpress/i18n', () => ( {
	__: jest.fn( ( text ) => text ),
} ) );

jest.mock( '@wordpress/date', () => ( {
	getSettings: jest.fn( () => ( {
		formats: {
			datetime: 'Y-m-d H:i:s',
			date: 'Y-m-d',
		},
	} ) ),
	humanTimeDiff: jest.fn( ( date1, date2 ) => '2 hours ago' ),
	dateI18n: jest.fn( ( format, date ) => '2024-01-01' ),
	getDate: jest.fn( ( dateString ) => {
		if ( dateString === null ) {
			return new Date();
		}
		return new Date( dateString );
	} ),
} ) );

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'data.tsx', () => {
	beforeEach( () => {
		// Reset global window object
		delete global.window.shepherdData;
		global.window.ajaxurl = 'http://example.com/wp-admin/admin-ajax.php';
		jest.clearAllMocks();
		// Clear module cache to reset unique values and defaultArgs
		jest.resetModules();
	} );

	describe( 'getFields', () => {
		const mockData = [
			{
				id: 1,
				data: { task_class: 'TestTask' },
				status: 'pending',
			},
			{
				id: 2,
				data: { task_class: 'AnotherTask' },
				status: 'complete',
			},
		];

		it( 'should return all field definitions', () => {
			const { getFields } = require( '../../../app/data' );
			const fields = getFields( mockData );

			expect( fields ).toHaveLength( 7 );
			expect( fields.map( ( f ) => f.id ) ).toEqual( [
				'id',
				'action_id',
				'task_type',
				'task_args',
				'current_try',
				'status',
				'scheduled_at',
			] );
		} );

		it( 'should configure task ID field correctly', () => {
			const { getFields } = require( '../../../app/data' );
			const fields = getFields( mockData );
			const idField = fields.find( ( f ) => f.id === 'id' );

			expect( idField ).toBeDefined();
			expect( idField.label ).toBe( 'Task ID' );
			expect( idField.enableHiding ).toBe( false );
			expect( idField.enableSorting ).toBe( true );
		} );

		it( 'should configure task type field with getValue and elements', () => {
			const { getFields } = require( '../../../app/data' );
			const fields = getFields( mockData );
			const taskTypeField = fields.find( ( f ) => f.id === 'task_type' );

			expect( taskTypeField ).toBeDefined();
			expect( taskTypeField.getValue ).toBeDefined();
			expect( taskTypeField.filterBy ).toEqual( {
				operators: [ 'is' ],
				isPrimary: true,
			} );
			expect( taskTypeField.elements ).toBeDefined();

			// Test getValue function
			const item = { data: { task_class: 'MyTask' } };
			expect( taskTypeField.getValue( { item } ) ).toBe( 'MyTask' );
		} );

		it( 'should configure task args field with custom render', () => {
			const { getFields } = require( '../../../app/data' );
			const fields = getFields( mockData );
			const argsField = fields.find( ( f ) => f.id === 'task_args' );

			expect( argsField ).toBeDefined();
			expect( argsField.render ).toBeDefined();

			// Test render function
			const item = { data: { args: [ 'arg1', 'arg2' ] } };
			const { container } = render( <div>{ argsField.render( { item } ) }</div> );
			const codeElement = container.querySelector( 'code' );

			expect( codeElement ).toBeInTheDocument();
			expect( codeElement ).toHaveStyle( {
				whiteSpace: 'pre-wrap',
				wordWrap: 'break-word',
			} );
		} );

		it( 'should configure status field with elements and filtering', () => {
			const { getFields } = require( '../../../app/data' );
			const fields = getFields( mockData );
			const statusField = fields.find( ( f ) => f.id === 'status' );

			expect( statusField ).toBeDefined();
			expect( statusField.elements ).toHaveLength( 5 );
			expect( statusField.elements[ 0 ] ).toEqual( {
				value: 'pending',
				label: 'Pending',
			} );
			expect( statusField.filterBy ).toEqual( {
				operators: [ 'is', 'isNot' ],
				isPrimary: true,
			} );
		} );

		it( 'should configure scheduled_at field with date rendering', () => {
			const { getFields } = require( '../../../app/data' );
			const fields = getFields( mockData );
			const scheduledField = fields.find( ( f ) => f.id === 'scheduled_at' );

			expect( scheduledField ).toBeDefined();
			expect( scheduledField.render ).toBeDefined();

			// Test render with null date
			const itemNoDate = { scheduled_at: null };
			const { container: containerNoDate } = render(
				<div>{ scheduledField.render( { item: itemNoDate } ) }</div>
			);
			expect( containerNoDate ).toHaveTextContent( 'Never' );

			// Test render with recent date
			const recentDate = new Date();
			const itemRecent = { scheduled_at: recentDate };
			const { container: containerRecent } = render(
				<div>{ scheduledField.render( { item: itemRecent } ) }</div>
			);
			expect( containerRecent ).toHaveTextContent( '2 hours ago' );

			// Test render with old date
			const oldDate = new Date( '2020-01-01' );
			const itemOld = { scheduled_at: oldDate };
			const { getDate } = require( '@wordpress/date' );
			getDate.mockReturnValueOnce( new Date( '2024-01-01' ) );
			const { container: containerOld } = render(
				<div>{ scheduledField.render( { item: itemOld } ) }</div>
			);
			expect( containerOld ).toHaveTextContent( '2024-01-01' );
		} );
	} );

	describe( 'getTasks', () => {
		const apiFetch = require( '@wordpress/api-fetch' );

		it( 'should return data from window when using default args', async () => {
			const defaultArgs = {
				perPage: 10,
				page: 1,
				order: 'desc',
				orderby: 'id',
				search: '',
				filters: '[]',
			};

			global.window.shepherdData = {
				defaultArgs,
				tasks: [
					{
						id: 1,
						action_id: 100,
						data: { task_class: 'TestTask', args: [ 'arg1' ] },
						current_try: 1,
						status: 'pending',
						scheduled_at: { date: '2024-01-01 12:00:00' },
						logs: [],
					},
				],
				totalItems: 1,
				totalPages: 1,
			};

			// Import fresh module after setting window data
			const { getTasks } = require( '../../../app/data' );
			const result = await getTasks( defaultArgs );

			expect( result.data ).toHaveLength( 1 );
			expect( result.data[ 0 ].id ).toBe( 1 );
			expect( result.data[ 0 ].scheduled_at ).toBeInstanceOf( Date );
			expect( result.paginationInfo.totalItems ).toBe( 1 );
			expect( apiFetch ).not.toHaveBeenCalled();
		} );


		it( 'should handle API errors gracefully', async () => {
			global.window.shepherdData = {
				defaultArgs: { perPage: 10 },
				nonce: 'test-nonce',
			};

			// Import fresh module after setting window data
			const { getTasks } = require( '../../../app/data' );

			apiFetch.mockRejectedValue( new Error( 'Network error' ) );

			const result = await getTasks( { perPage: 20 } );

			expect( result.data ).toEqual( [] );
			expect( result.paginationInfo ).toEqual( {
				totalItems: 0,
				totalPages: 0,
			} );
		} );

		it( 'should handle unsuccessful API response', async () => {
			global.window.shepherdData = {
				defaultArgs: { perPage: 10 },
				nonce: 'test-nonce',
			};

			// Import fresh module after setting window data
			const { getTasks } = require( '../../../app/data' );

			apiFetch.mockResolvedValue( {
				success: false,
				data: { message: 'Error occurred' },
			} );

			const result = await getTasks( { perPage: 20 } );

			expect( result.data ).toEqual( [] );
			expect( result.paginationInfo ).toEqual( {
				totalItems: 0,
				totalPages: 0,
			} );
		} );

		it( 'should handle scheduled_at date conversion', async () => {
			const { getDate } = require( '@wordpress/date' );
			const mockDate = new Date( '2024-01-01T12:00:00Z' );
			getDate.mockReturnValue( mockDate );

			const defaultArgs = { perPage: 10, page: 1, order: 'desc', orderby: 'id', search: '', filters: '[]' };
			global.window.shepherdData = {
				defaultArgs,
				tasks: [
					{
						id: 1,
						action_id: 100,
						data: { task_class: 'TestTask', args: [] },
						current_try: 1,
						status: 'pending',
						scheduled_at: { date: '2024-01-01 12:00:00' },
						logs: [],
					},
				],
			};

			// Import fresh module after setting window data
			const { getTasks } = require( '../../../app/data' );
			const result = await getTasks( defaultArgs );

			expect( getDate ).toHaveBeenCalledWith( '2024-01-01 12:00:00' );
			expect( result.data[ 0 ].scheduled_at ).toBe( mockDate );
		} );
	} );

	describe( 'getPaginationInfo', () => {
		it( 'should return pagination info from window data', () => {
			global.window.shepherdData = {
				totalItems: 100,
				totalPages: 10,
			};

			const { getPaginationInfo } = require( '../../../app/data' );
			const paginationInfo = getPaginationInfo();

			expect( paginationInfo ).toEqual( {
				totalItems: 100,
				totalPages: 10,
			} );
		} );

		it( 'should return zero values when no data', () => {
			const { getPaginationInfo } = require( '../../../app/data' );
			const paginationInfo = getPaginationInfo();

			expect( paginationInfo ).toEqual( {
				totalItems: 0,
				totalPages: 0,
			} );
		} );

		it( 'should handle partial data', () => {
			global.window.shepherdData = {
				totalItems: 50,
			};

			const { getPaginationInfo } = require( '../../../app/data' );
			const paginationInfo = getPaginationInfo();

			expect( paginationInfo ).toEqual( {
				totalItems: 50,
				totalPages: 0,
			} );
		} );
	} );

	describe( 'getUniqueValuesOfData', () => {
		const mockTasks = [
			{ id: 1, status: 'pending', data: { task_class: 'EmailTask' } },
			{ id: 2, status: 'complete', data: { task_class: 'EmailTask' } },
			{ id: 3, status: 'pending', data: { task_class: 'HTTPTask' } },
			{ id: 4, status: 'failed', data: { task_class: 'HTTPTask' } },
		];


		it( 'should extract unique values from top-level fields', () => {
			const { getUniqueValuesOfData } = require( '../../../app/data' );
			const result = getUniqueValuesOfData( 'status', mockTasks );

			expect( result ).toHaveLength( 3 );
			expect( result ).toEqual( [
				{ label: 'pending', value: 'pending' },
				{ label: 'complete', value: 'complete' },
				{ label: 'failed', value: 'failed' },
			] );
		} );

		it( 'should extract unique values from nested data fields', () => {
			const { getUniqueValuesOfData } = require( '../../../app/data' );
			const result = getUniqueValuesOfData( 'task_class', mockTasks );

			expect( result ).toHaveLength( 2 );
			expect( result ).toEqual( [
				{ label: 'EmailTask', value: 'EmailTask' },
				{ label: 'HTTPTask', value: 'HTTPTask' },
			] );
		} );

		it( 'should cache unique values across multiple calls', () => {
			const { getUniqueValuesOfData } = require( '../../../app/data' );
			// First call
			getUniqueValuesOfData( 'status', mockTasks.slice( 0, 2 ) );
			
			// Second call with additional data
			const result = getUniqueValuesOfData( 'status', mockTasks.slice( 2, 4 ) );

			// Should have all unique values from both calls
			expect( result ).toHaveLength( 3 );
		} );

		it( 'should handle undefined values', () => {
			const { getUniqueValuesOfData } = require( '../../../app/data' );
			const tasksWithUndefined = [
				{ id: 1, status: 'pending', data: {} },
				{ id: 2, data: {} }, // status is undefined
				{ id: 3, status: 'complete', data: {} },
			];

			const result = getUniqueValuesOfData( 'status', tasksWithUndefined );

			expect( result ).toHaveLength( 3 );
			expect( result ).toEqual( [
				{ label: 'pending', value: 'pending' },
				{ label: undefined, value: undefined },
				{ label: 'complete', value: 'complete' },
			] );
		} );
	} );
} );

// Type augmentation for window object
declare global {
	interface Window {
		shepherdData?: {
			tasks?: any[];
			totalItems?: number;
			totalPages?: number;
			defaultArgs?: any;
			nonce?: string;
		};
		ajaxurl?: string;
	}
}