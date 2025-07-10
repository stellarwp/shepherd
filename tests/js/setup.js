// Jest setup file
import '@testing-library/jest-dom';

// Mock console methods to avoid cluttering test output
global.console = {
	...console,
	log: jest.fn(),
	debug: jest.fn(),
	info: jest.fn(),
	warn: jest.fn(),
	error: jest.fn(),
};