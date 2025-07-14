import type { SortDirection } from '@wordpress/dataviews';

export type TaskData = {
	args: any[];
	task_class: string;
};

export type ValidStatusSlug =
	| 'complete'
	| 'pending'
	| 'in-progress'
	| 'failed'
	| 'cancelled';

export type ValidLogType =
	| 'created'
	| 'started'
	| 'rescheduled'
	| 'retrying'
	| 'failed';

export type ValidLogLevel =
	| 'info'
	| 'notice'
	| 'warning'
	| 'error'
	| 'critical'
	| 'alert'
	| 'emergency';

export type Log = {
	id: number;
	task_id: number;
	action_id: number;
	date: Date;
	level: ValidLogLevel;
	type: ValidLogType;
	entry: string;
};

export type Task = {
	id: number;
	action_id: number;
	data: TaskData;
	current_try: number;
	status: {
		slug: ValidStatusSlug;
		label: string;
	};
	scheduled_at: Date | null;
	logs: Log[];
};

export type PaginationInfo = {
	totalItems: number;
	totalPages: number;
};

export type FieldValue = {
	label: string | number;
	value: string | number | boolean;
};

export type TaskArgs = {
	perPage?: number;
	page?: number;
	order?: SortDirection;
	orderby?: string;
	search?: string;
	filters?: string;
};

export type AjaxTasksResponse = {
	success: boolean;
	data: {
		tasks: Task[];
		totalItems: number;
		totalPages: number;
	};
};
