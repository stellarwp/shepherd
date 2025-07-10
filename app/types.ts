export type TaskData = {
	args: any[];
	task_class: string;
};

export type ValidStatusSlug =
	| 'pending'
	| 'running'
	| 'success'
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
