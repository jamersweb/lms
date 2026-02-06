import { ref } from 'vue';

const toast = ref({
  show: false,
  message: '',
  type: 'info',
});

export function useToast() {
  const showToast = (message, type = 'info', duration = 5000) => {
    toast.value = {
      show: true,
      message,
      type,
      duration,
    };
  };

  const showSuccess = (message, duration = 5000) => {
    showToast(message, 'success', duration);
  };

  const showError = (message, duration = 5000) => {
    showToast(message, 'error', duration);
  };

  const showWarning = (message, duration = 5000) => {
    showToast(message, 'warning', duration);
  };

  const showInfo = (message, duration = 5000) => {
    showToast(message, 'info', duration);
  };

  const hideToast = () => {
    toast.value.show = false;
  };

  return {
    toast,
    showToast,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    hideToast,
  };
}
