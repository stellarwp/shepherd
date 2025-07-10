import React from 'react';
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';
import { getFields, getTasks, getPaginationInfo } from '../../../app/data';

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

describe( 'data.tsx', () => {
	beforeEach( () => {
		// Reset global window object
		global.window.shepherdData = undefined;
	} );

	describe( 'getFields', () => {
		it( 'should return all field definitions', () => {
			const fields = getFields();

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
			const fields = getFields();
			const idField = fields.find( ( f ) => f.id === 'id' );

			expect( idField ).toBeDefined();
			expect( idField.label ).toBe( 'Task ID' );
			expect( idField.enableHiding ).toBe( false );
			expect( idField.enableSorting ).toBe( true );
		} );

		it( 'should configure task type field with getValue', () => {
			const fields = getFields();
			const taskTypeField = fields.find( ( f ) => f.id === 'task_type' );

			expect( taskTypeField ).toBeDefined();
			expect( taskTypeField.getValue ).toBeDefined();

			// Test getValue function
			const item = { data: { task_class: 'MyTask' } };
			expect( taskTypeField.getValue( { item } ) ).toBe( 'MyTask' );
		} );

		it( 'should configure task args field with custom render', () => {
			const fields = getFields();
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

		it( 'should configure status field with elements', () => {
			const fields = getFields();
			const statusField = fields.find( ( f ) => f.id === 'status' );

			expect( statusField ).toBeDefined();
			expect( statusField.elements ).toHaveLength( 5 );
			expect( statusField.elements[ 0 ] ).toEqual( {
				value: 'pending',
				label: 'Pending',
			} );
		} );

		it( 'should configure scheduled_at field with date rendering', () => {
			const fields = getFields();
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
		it( 'should return empty array when no data', () => {
			const tasks = getTasks( 1, 10 );
			expect( tasks ).toEqual( [] );
		} );

		it( 'should return empty array when shepherdData has no tasks', () => {
			global.window.shepherdData = {};
			const tasks = getTasks( 1, 10 );
			expect( tasks ).toEqual( [] );
		} );

		it( 'should transform tasks correctly', () => {
			global.window.shepherdData = {
				tasks: [
					{
						id: 1,
						action_id: 100,
						data: { task_class: 'TestTask', args: [ 'arg1' ] },
						current_try: 1,
						status: { slug: 'pending', label: 'Pending' },
						scheduled_at: { date: '2024-01-01 12:00:00' },
						logs: [],
					},
					{
						id: 2,
						action_id: 101,
						data: { task_class: 'AnotherTask', args: [] },
						current_try: 2,
						status: { slug: 'running', label: 'Running' },
						scheduled_at: null,
						logs: [ { id: 1 } ],
					},
				],
			};

			const tasks = getTasks( 1, 10 );

			expect( tasks ).toHaveLength( 2 );
			expect( tasks[ 0 ] ).toMatchObject( {
				id: 1,
				action_id: 100,
				current_try: 1,
				status: { slug: 'pending', label: 'Pending' },
			} );
			expect( tasks[ 0 ].scheduled_at ).toBeInstanceOf( Date );
			expect( tasks[ 1 ].scheduled_at ).toBeNull();
		} );

		it( 'should handle scheduled_at date conversion', () => {
			const { getDate } = require( '@wordpress/date' );
			const mockDate = new Date( '2024-01-01T12:00:00Z' );
			getDate.mockReturnValue( mockDate );

			global.window.shepherdData = {
				tasks: [
					{
						id: 1,
						scheduled_at: { date: '2024-01-01 12:00:00' },
					},
				],
			};

			const tasks = getTasks( 1, 10 );

			expect( getDate ).toHaveBeenCalledWith( '2024-01-01 12:00:00' );
			expect( tasks[ 0 ].scheduled_at ).toBe( mockDate );
		} );
	} );

	describe( 'getPaginationInfo', () => {
		it( 'should return pagination info from window data', () => {
			global.window.shepherdData = {
				totalItems: 100,
				totalPages: 10,
			};

			const paginationInfo = getPaginationInfo();

			expect( paginationInfo ).toEqual( {
				totalItems: 100,
				totalPages: 10,
			} );
		} );

		it( 'should return undefined values when no data', () => {
			const paginationInfo = getPaginationInfo();

			expect( paginationInfo ).toEqual( {
				totalItems: undefined,
				totalPages: undefined,
			} );
		} );

		it( 'should handle partial data', () => {
			global.window.shepherdData = {
				totalItems: 50,
			};

			const paginationInfo = getPaginationInfo();

			expect( paginationInfo ).toEqual( {
				totalItems: 50,
				totalPages: undefined,
			} );
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
		};
	}
}
