import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/technicianTaskService', () => ({
    default: {
        list: vi.fn(),
        onTheWay: vi.fn(),
        start: vi.fn(),
        submit: vi.fn(),
        cancel: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            technician_tasks: {
                action_on_the_way: 'في الطريق',
                action_start: 'ابدأ',
                action_resume: 'استئناف',
                load_error: 'تعذر تحميل المهام',
                action_error: 'تعذر تنفيذ الإجراء',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useTechnicianTasks } = await import('./useTechnicianTasks');
const technicianTaskService = (await import('@/services/technicianTaskService')).default;

describe('useTechnicianTasks', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with empty tasks, default pagination, no error, activeCount 0, and the NEXT_ACTION/CANCELLABLE_STATUSES maps', () => {
        const { tasks, pagination, isLoading, error, activeCount, actingId, actionError, NEXT_ACTION, CANCELLABLE_STATUSES } =
            useTechnicianTasks();

        expect(tasks.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(activeCount.value).toBe(0);
        expect(actingId.value).toBeNull();
        expect(actionError.value).toBeNull();

        expect(CANCELLABLE_STATUSES).toEqual(['assigned', 'on_the_way', 'in_progress', 'waiting_parts']);
        expect(NEXT_ACTION.assigned).toEqual({ action: 'onTheWay', label: 'في الطريق', icon: 'fa-route', tone: 'primary' });
        expect(NEXT_ACTION.on_the_way).toEqual({ action: 'start', label: 'ابدأ', icon: 'fa-play', tone: 'primary' });
        expect(NEXT_ACTION.waiting_parts).toEqual({ action: 'start', label: 'استئناف', icon: 'fa-play', tone: 'primary' });
        expect(NEXT_ACTION.in_progress).toBeNull();
    });

    it('activeCount excludes approved/rejected/cancelled tasks', async () => {
        technicianTaskService.list.mockResolvedValue({
            data: {
                data: {
                    data: [
                        { id: 1, status: 'assigned' },
                        { id: 2, status: 'approved' },
                        { id: 3, status: 'rejected' },
                        { id: 4, status: 'cancelled' },
                        { id: 5, status: 'in_progress' },
                    ],
                    meta: {},
                },
            },
        });

        const { fetchTasks, activeCount } = useTechnicianTasks();
        await fetchTasks();

        expect(activeCount.value).toBe(2); // assigned + in_progress
    });

    it('fetchTasks with no status omits the status param', async () => {
        technicianTaskService.list.mockResolvedValue({ data: { data: [] } });

        const { fetchTasks } = useTechnicianTasks();
        await fetchTasks();

        expect(technicianTaskService.list).toHaveBeenCalledWith({ page: 1 });
    });

    it('fetchTasks forwards a server-filterable status', async () => {
        technicianTaskService.list.mockResolvedValue({ data: { data: [] } });

        const { fetchTasks } = useTechnicianTasks();
        await fetchTasks(2, 'submitted');

        expect(technicianTaskService.list).toHaveBeenCalledWith({ page: 2, status: 'submitted' });
    });

    it('fetchTasks does NOT forward "active" (a client-side-only aggregate status not supported by the backend)', async () => {
        technicianTaskService.list.mockResolvedValue({ data: { data: [] } });

        const { fetchTasks } = useTechnicianTasks();
        await fetchTasks(1, 'active');

        expect(technicianTaskService.list).toHaveBeenCalledWith({ page: 1 });
    });

    it('fetchTasks populates tasks/pagination on success', async () => {
        technicianTaskService.list.mockResolvedValue({
            data: {
                data: {
                    data: [{ id: 1, status: 'assigned' }],
                    meta: { current_page: 1, last_page: 3, total: 30, per_page: 15 },
                },
            },
        });

        const { fetchTasks, tasks, pagination } = useTechnicianTasks();
        await fetchTasks();

        expect(tasks.value).toEqual([{ id: 1, status: 'assigned' }]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 3, total: 30, per_page: 15 });
    });

    it('reshapes a fetchTasks failure into the translated fallback message', async () => {
        technicianTaskService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchTasks, error, isLoading } = useTechnicianTasks();
        await fetchTasks();

        expect(error.value).toBe('تعذر تحميل المهام');
        expect(isLoading.value).toBe(false);
    });

    it('performAction tracks actingId while in flight, dynamically dispatches to the named service method, replaces the task, and returns true', async () => {
        technicianTaskService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1, status: 'assigned' }], meta: {} } },
        });
        technicianTaskService.onTheWay.mockResolvedValue({ data: { data: { id: 1, status: 'on_the_way' } } });

        const { fetchTasks, performAction, tasks, actingId, actionError } = useTechnicianTasks();
        await fetchTasks();

        const promise = performAction(1, 'onTheWay');
        expect(actingId.value).toBe(1);
        const result = await promise;

        expect(result).toBe(true);
        expect(technicianTaskService.onTheWay).toHaveBeenCalledWith(1, undefined);
        expect(tasks.value[0]).toEqual({ id: 1, status: 'on_the_way' });
        expect(actingId.value).toBeNull();
        expect(actionError.value).toBeNull();
    });

    it('performAction forwards a payload through to the dispatched method (e.g. submit with completion notes)', async () => {
        technicianTaskService.submit.mockResolvedValue({ data: { data: { id: 2, status: 'submitted' } } });

        const { performAction } = useTechnicianTasks();
        await performAction(2, 'submit', 'تم الفحص وإصلاح العطل');

        expect(technicianTaskService.submit).toHaveBeenCalledWith(2, 'تم الفحص وإصلاح العطل');
    });

    it('performAction reshapes a failure into the translated fallback message and clears actingId', async () => {
        technicianTaskService.cancel.mockRejectedValue({ message: 'Network Error' });

        const { performAction, actionError, actingId } = useTechnicianTasks();
        const result = await performAction(3, 'cancel');

        expect(result).toBe(false);
        expect(actionError.value).toBe('تعذر تنفيذ الإجراء');
        expect(actingId.value).toBeNull();
    });
});
