import React, { useState } from 'react';
import { useToast } from '@/hooks/use-toast';
import { ConnectionCard } from './ConnectionCard';
import { EventDisplay } from './EventDisplay';
import { TransactionalCard } from './TransactionalCard';
import { EventTrackingCard } from './EventTrackingCard';
import { PluginsCard } from './PluginsCard';
import { ConnectionDialog } from '@/components/ConnectionDialog/ConnectionDialog.jsx';


export default function BentoSettings() {
  const { toast } = useToast();
  const [settings, setSettings] = useState(window.bentoAdmin?.settings || {});
  const [showDialog, setShowDialog] = useState(!window.bentoAdmin?.settings?.bento_site_key);

  const updateSetting = async (key, value) => {
    try {
      const response = await fetch(window.bentoAdmin.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'bento_update_settings',
          _wpnonce: window.bentoAdmin.nonce,
          key,
          value
        })
      });

      const data = await response.json();
      if (data.success) {
        setSettings(prev => ({ ...prev, [key]: value }));
        toast({
          title: "Settings Updated",
          description: "Your changes have been saved successfully."
        });
      }
    } catch (error) {
      toast({
        title: "Error",
        description: "Failed to save settings",
        variant: "destructive"
      });
    }
  };

  const handleConnected = () => {
    setShowDialog(false);
    window.location.reload();
  };

  return (
    <div className="p-6">
      {showDialog && (
        <ConnectionDialog
          onConnected={handleConnected}
          onDismiss={() => setShowDialog(false)}
        />
      )}
      <div className="columns-2 gap-6">
        <div className="mb-6 break-inside-avoid-column">
          <ConnectionCard settings={settings} onUpdate={updateSetting} />
        </div>
        {settings.bento_site_key && (
          <>
            <div className="pb-6 break-inside-avoid-column">
              <EventDisplay />
            </div>
            <div className="pb-6 break-inside-avoid-column">
              <TransactionalCard settings={settings} onUpdate={updateSetting} />
            </div>
            <div className="pb-6 break-inside-avoid-column">
              <EventTrackingCard settings={settings} onUpdate={updateSetting} />
            </div>
            <div className="pb-6 break-inside-avoid-column">
              <PluginsCard />
            </div>
          </>
        )}
      </div>
    </div>
  );
}