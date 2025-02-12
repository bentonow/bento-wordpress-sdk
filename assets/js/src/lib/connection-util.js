export const getConnectionStatus = (settings) => {
  const defaultStatus = { connected: false, message: 'Not connected', code: 401 };

  if (!settings.bento_connection_status) {
    return defaultStatus;
  }

  try {
    const status = typeof settings.bento_connection_status === 'string'
      ? JSON.parse(settings.bento_connection_status)
      : settings.bento_connection_status;

    return {
      connected: status.connected ?? false,
      message: status.message ?? defaultStatus.message,
      code: status.code ?? defaultStatus.code,
      timestamp: status.timestamp
    };
  } catch (e) {
    console.error('Failed to parse connection status:', e);
    return defaultStatus;
  }
};

export const getBadgeVariant = (status) => {
  if (status.connected) return 'success';
  if (status.code >= 500) return 'destructive';
  return 'secondary';
};

export const callBentoApi = async (action, data = {}) => {
  const response = await fetch(window.bentoAdmin.ajaxUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      action,
      _wpnonce: window.bentoAdmin.nonce,
      ...data
    })
  });

  const result = await response.json();

  if (!result.success && !result.connection_status) {
    throw new Error(result.message || 'API call failed');
  }

  return result;
};