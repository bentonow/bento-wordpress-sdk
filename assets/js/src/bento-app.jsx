import React from 'react';
import { createRoot } from 'react-dom/client';
import '../../css/bento-admin.css';

import MailLogs from '@/components/MailLogs';

import { Toaster } from '@/components/ui/toaster';
import BentoSettings from '@/components/BentoSettings/index.jsx';

document.addEventListener('DOMContentLoaded', () => {
  // Handle Mail Logs Component Mount
  const mailLogsContainer = document.getElementById('bento-mail-logs');
  if (mailLogsContainer) {
    const root = createRoot(mailLogsContainer);
    root.render(
      <>
        <MailLogs
          logs={window.bentoAdmin?.mailLogs || []}
          nonce={window.bentoAdmin?.nonce || ''}
          adminUrl={window.bentoAdmin?.adminUrl || ''}
        />
        <Toaster />
      </>
    );
  }

  // Handle Settings Component Mount
  const settingsContainer = document.getElementById('bento-settings');
  if (settingsContainer) {
    const root = createRoot(settingsContainer);
    root.render(
      <>
        <BentoSettings />
        <Toaster />
      </>
    );
  }
});