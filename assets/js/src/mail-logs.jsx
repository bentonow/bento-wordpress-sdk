import '@/css/admin.css';  // Using the @ alias which points to /assets
import React from 'react';
import { createRoot } from 'react-dom/client';
import MailLogs from './components/MailLogs';
import { Toaster } from './components/ui/toaster';

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('bento-mail-logs');
  if (container) {
    const root = createRoot(container);
    root.render(
      <>
        <MailLogs
          logs={window.bentoMailLogs?.logs || []}
          nonce={window.bentoMailLogs?.nonce || ''}
          adminUrl={window.bentoMailLogs?.adminUrl || ''}
        />
        <Toaster />
      </>
    );
  }
});