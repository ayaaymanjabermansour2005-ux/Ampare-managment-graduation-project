import { reactive } from "vue";
import i18n from "@/i18n";

const state = reactive({
  isOpen: false,
  title: "",
  message: "",
  confirmLabel: "",
  cancelLabel: "",
  variant: "default",
  hideCancel: false,
});

let resolvePromise = null;

function confirm(options) {
  state.title = options.title ?? i18n.global.t("common.confirm_action_title");
  state.message = options.message ?? "";
  state.confirmLabel = options.confirmLabel ?? i18n.global.t("common.confirm");
  state.cancelLabel = options.cancelLabel ?? i18n.global.t("common.cancel");
  state.variant = options.variant ?? "default";
  state.hideCancel = options.hideCancel ?? false;
  state.isOpen = true;

  return new Promise((resolve) => {
    resolvePromise = resolve;
  });
}

function handleConfirm() {
  state.isOpen = false;
  resolvePromise?.(true);
  resolvePromise = null;
}

function handleCancel() {
  state.isOpen = false;
  resolvePromise?.(false);
  resolvePromise = null;
}

export function useConfirm() {
  return { confirm };
}

export function useConfirmDialogState() {
  return { state, handleConfirm, handleCancel };
}
