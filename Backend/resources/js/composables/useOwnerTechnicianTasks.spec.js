import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/technicianTaskService', () => ({
    default: {
        list: vi.fn(),
        review: vi.fn(),
        rate: vi.fn(),
        create: vi.fn(),
        assign: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_technician_tasks: {
                load_error: 'تعذر تحميل المهام',
                review_error: 'تعذر مراجعة المهمة',
                rate_error: 'تعذر تقييم المهمة',
                create_error: 'تعذر إنشاء المهمة',
                assign_error: 'تعذر إسناد المهمة',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerTechnicianTasks } = await import('./useOwnerTechnicianTasks');
const technicianTaskService = (await import('@/services/technicianTaskService')).default;

describe('useOwnerTechnicianTasks', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with empty tasks, default pagination, and empty derived buckets', () => {
        const { tasks, pagination, isLoading, error, needsReviewTasks, needsRatingTasks, otherTasks, actingId, actionError } = useOwnerTechnicianTasks();

        expect(tasks.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(needsReviewTasks.value).toEqual([]);
        expect(needsRatingTasks.value).toEqual([]);
        expect(otherTasks.value).toEqual([]);
        expect(actingId.value).toBeNull();
        expect(actionError.value).toBeNull();
    });

    it('fetchTasks populates tasks/pagination, and the three derived buckets classify by status/rating', async () => {
        technicianTaskService.list.mockResolvedValue({
            data: {
                data: {
                    data: [
                        { id: 1, status: 'submitted' },
                        { id: 2, status: 'approved', rating: null },
                        { id: 3, status: 'approved', rating: { stars: 5 } },
                        { id: 4, status: 'cancelled' },
                    ],
                    meta: { current_page: 1, last_page: 1, total: 4, per_page: 15 },
                },
            },
        });

        const { fetchTasks, tasks, needsReviewTasks, needsRatingTasks, otherTasks } = useOwnerTechnicianTasks();
        await fetchTasks();

        expect(technicianTaskService.list).toHaveBeenCalledWith({ page: 1 });
        expect(tasks.value).toHaveLength(4);
        expect(needsReviewTasks.value).toEqual([{ id: 1, status: 'submitted' }]);
        expect(needsRatingTasks.value).toEqual([{ id: 2, status: 'approved', rating: null }]);
        expect(otherTasks.value).toEqual([
            { id: 3, status: 'approved', rating: { stars: 5 } },
            { id: 4, status: 'cancelled' },
        ]);
    });

    it('reshapes a fetchTasks failure into the translated fallback message', async () => {
        technicianTaskService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchTasks, error, isLoading } = useOwnerTechnicianTasks();
        await fetchTasks();

        expect(error.value).toBe('تعذر تحميل المهام');
        expect(isLoading.value).toBe(false);
    });

    it('reviewTask tracks actingId while in flight, replaces the task in the list, and returns true on success', async () => {
        technicianTaskService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1, status: 'submitted' }], meta: {} } },
        });
        technicianTaskService.review.mockResolvedValue({ data: { data: { id: 1, status: 'approved' } } });

        const { fetchTasks, reviewTask, tasks, actingId, actionError, isCreating } = useOwnerTechnicianTasks();
        await fetchTasks();

        const promise = reviewTask(1, 'approve');
        expect(actingId.value).toBe(1);
        const result = await promise;

        expect(result).toBe(true);
        expect(technicianTaskService.review).toHaveBeenCalledWith(1, { decision: 'approve', rejection_reason: null });
        expect(tasks.value[0]).toEqual({ id: 1, status: 'approved' });
        expect(actingId.value).toBeNull();
        expect(actionError.value).toBeNull();
    });

    it('reviewTask passes the rejection reason through and reshapes a failure into actionError', async () => {
        technicianTaskService.review.mockRejectedValue({
            response: { status: 422, data: { message: 'سبب الرفض مطلوب.' } },
        });

        const { reviewTask, actionError, actingId } = useOwnerTechnicianTasks();
        const result = await reviewTask(1, 'reject', 'غير مطابق للمواصفات');

        expect(result).toBe(false);
        expect(technicianTaskService.review).toHaveBeenCalledWith(1, { decision: 'reject', rejection_reason: 'غير مطابق للمواصفات' });
        expect(actionError.value).toBe('سبب الرفض مطلوب.');
        expect(actingId.value).toBeNull();
    });

    it('rateTask sets the rating on the matching task object in place and returns true on success', async () => {
        technicianTaskService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1, status: 'approved', rating: null }], meta: {} } },
        });
        technicianTaskService.rate.mockResolvedValue({ data: { data: { stars: 5, comment: 'ممتاز' } } });

        const { fetchTasks, rateTask, tasks } = useOwnerTechnicianTasks();
        await fetchTasks();

        const result = await rateTask(1, 5, 'ممتاز');

        expect(result).toBe(true);
        expect(technicianTaskService.rate).toHaveBeenCalledWith(1, { rating: 5, comment: 'ممتاز' });
        expect(tasks.value[0].rating).toEqual({ stars: 5, comment: 'ممتاز' });
    });

    it('rateTask is a no-op on the list (but still reports success) when the task id is not found', async () => {
        technicianTaskService.rate.mockResolvedValue({ data: { data: { stars: 3 } } });

        const { rateTask, tasks } = useOwnerTechnicianTasks();
        const result = await rateTask(999, 3, '');

        expect(result).toBe(true);
        expect(tasks.value).toEqual([]);
    });

    it('rateTask reshapes a failure into actionError', async () => {
        technicianTaskService.rate.mockRejectedValue({ message: 'Network Error' });

        const { rateTask, actionError } = useOwnerTechnicianTasks();
        const result = await rateTask(1, 5, '');

        expect(result).toBe(false);
        expect(actionError.value).toBe('تعذر تقييم المهمة');
    });

    it('createTask refetches page 1 and returns true on success', async () => {
        technicianTaskService.create.mockResolvedValue({ data: {} });
        technicianTaskService.list.mockResolvedValue({
            data: { data: { data: [{ id: 9 }], meta: {} } },
        });

        const { createTask, tasks, isCreating, createError } = useOwnerTechnicianTasks();
        const result = await createTask({ title: 'مهمة جديدة' });

        expect(result).toBe(true);
        expect(technicianTaskService.create).toHaveBeenCalledWith({ title: 'مهمة جديدة' });
        expect(tasks.value).toEqual([{ id: 9 }]);
        expect(isCreating.value).toBe(false);
        expect(createError.value).toBeNull();
    });

    it('createTask reshapes a failure into createError', async () => {
        technicianTaskService.create.mockRejectedValue({ message: 'Network Error' });

        const { createTask, createError, isCreating } = useOwnerTechnicianTasks();
        const result = await createTask({});

        expect(result).toBe(false);
        expect(createError.value).toBe('تعذر إنشاء المهمة');
        expect(isCreating.value).toBe(false);
    });

    it('assignTask replaces the task in the list and returns true on success', async () => {
        technicianTaskService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1, technician_id: null }], meta: {} } },
        });
        technicianTaskService.assign.mockResolvedValue({ data: { data: { id: 1, technician_id: 4 } } });

        const { fetchTasks, assignTask, tasks, isAssigning, assignError } = useOwnerTechnicianTasks();
        await fetchTasks();

        const result = await assignTask(1, 4);

        expect(result).toBe(true);
        expect(technicianTaskService.assign).toHaveBeenCalledWith(1, { technician_id: 4 });
        expect(tasks.value[0]).toEqual({ id: 1, technician_id: 4 });
        expect(isAssigning.value).toBe(false);
        expect(assignError.value).toBeNull();
    });

    it('assignTask reshapes a failure into assignError', async () => {
        technicianTaskService.assign.mockRejectedValue({ message: 'Network Error' });

        const { assignTask, assignError, isAssigning } = useOwnerTechnicianTasks();
        const result = await assignTask(1, 4);

        expect(result).toBe(false);
        expect(assignError.value).toBe('تعذر إسناد المهمة');
        expect(isAssigning.value).toBe(false);
    });
});
