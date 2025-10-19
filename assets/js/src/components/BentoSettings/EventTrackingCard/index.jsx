import React, { useState } from 'react';
import { Card, CardHeader, CardContent, CardTitle, CardDescription } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';

export function EventTrackingCard({ settings, onUpdate }) {
  const { toast } = useToast();
  const [purgeLoading, setPurgeLoading] = useState(false);
  const [verifyLoading, setVerifyLoading] = useState(false);
  const [testLoading, setTestLoading] = useState(false);

  // Handle purge debug log action
  const handlePurgeDebugLog = async () => {
    setPurgeLoading(true);
    try {
      const response = await fetch(window.bentoAdmin.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'bento_purge_debug_log',
          _wpnonce: window.bentoAdmin.nonce
        })
      });

      if (!response.ok) {
        let errorMessage = `HTTP ${response.status}: Failed to clear debug log`;
        try {
          const errorData = await response.json();
          errorMessage = errorData.data?.message || errorMessage;
        } catch {
          const errorText = await response.text();
          if (errorText) errorMessage = errorText;
        }
        toast({
          title: "Error",
          description: errorMessage,
          variant: "destructive"
        });
        return;
      }

      const data = await response.json();
      if (data.success) {
        toast({
          title: "Success",
          description: data.data.message,
          variant: "default"
        });
      } else {
        toast({
          title: "Error",
          description: data.data.message || "Failed to clear debug log. Please check file permissions",
          variant: "destructive"
        });
      }
    } catch (error) {
      toast({
        title: "Error",
        description: error.message || "Failed to clear debug log. Please check file permissions",
        variant: "destructive"
      });
    } finally {
      setPurgeLoading(false);
    }
  };

  // Handle verify events queue action
  const handleVerifyEventsQueue = async () => {
    setVerifyLoading(true);
    try {
      const response = await fetch(window.bentoAdmin.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'bento_verify_events_queue',
          _wpnonce: window.bentoAdmin.nonce
        })
      });

      if (!response.ok) {
        let errorMessage = `HTTP ${response.status}: Failed to clean event queue`;
        try {
          const errorData = await response.json();
          errorMessage = errorData.data?.message || errorMessage;
        } catch {
          const errorText = await response.text();
          if (errorText) errorMessage = errorText;
        }
        toast({
          title: "Error",
          description: errorMessage,
          variant: "destructive"
        });
        return;
      }

      const data = await response.json();
      if (data.success) {
        toast({
          title: "Success",
          description: data.data.message,
          variant: "default"
        });
      } else {
        toast({
          title: "Error",
          description: data.data.message || "Failed to clean event queue. Database operation failed",
          variant: "destructive"
        });
      }
    } catch (error) {
      toast({
        title: "Error",
        description: error.message || "Failed to clean event queue. Database operation failed",
        variant: "destructive"
      });
    } finally {
      setVerifyLoading(false);
    }
  };

  const handleTestEvent = async () => {
    setTestLoading(true);
    try {
      const response = await fetch(window.bentoAdmin.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'bento_send_event_notification',
          _wpnonce: window.bentoAdmin.nonce,
        })
      });

      if (!response.ok) {
        let errorMessage = `HTTP ${response.status}: Failed to send test event`;
        try {
          const errorData = await response.json();
          errorMessage = errorData.data?.message || errorMessage;
        } catch {
          const errorText = await response.text();
          if (errorText) errorMessage = errorText;
        }
        toast({
          title: 'Error',
          description: errorMessage,
          variant: 'destructive',
        });
        return;
      }

      const data = await response.json();

      if (data.success) {
        toast({
          title: 'Test Event Sent',
          description: 'A test event is being displayed in the live event sampling.',
        });
      } else {
        toast({
          title: 'Error',
          description: data.data?.message || 'Failed to send test event.',
          variant: 'destructive',
        });
      }
    } catch (error) {
      toast({
        title: 'Error',
        description: error.message || 'Failed to send test event.',
        variant: 'destructive',
      });
    } finally {
      setTestLoading(false);
    }
  };

  return (
    <Card className="mb-6 rounded-md break-inside-avoid-column">
      <CardHeader>
        <CardTitle>Event Tracking Settings</CardTitle>
        <CardDescription>
          Configure how Bento tracks events and user activity
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        <div className="flex items-center justify-between">
          <Label htmlFor="enable-tracking" className="flex flex-col space-y-1">
            <span>Enable Site Tracking</span>
            <span className="font-normal text-sm text-muted-foreground">
                            Enable Bento site tracking and Bento.js
                        </span>
          </Label>
          <Switch
            id="enable-tracking"
            checked={settings.bento_enable_tracking === '1'}
            onCheckedChange={(checked) => onUpdate('bento_enable_tracking', checked ? '1' : '0')}
          />
        </div>

        <div className="flex items-center justify-between">
          <Label htmlFor="enable-logging" className="flex flex-col space-y-1">
            <span>Debug Logging</span>
            <span className="font-normal text-sm text-muted-foreground">
                            Enable detailed logging for debugging
                        </span>
          </Label>
          <Switch
            id="enable-logging"
            checked={settings.bento_enable_logging === '1'}
            onCheckedChange={(checked) => onUpdate('bento_enable_logging', checked ? '1' : '0')}
          />
        </div>

        {/* Cleanup buttons - only show when debug logging is enabled */}
        {settings.bento_enable_logging === '1' && (
          <div className="flex gap-3 pt-2">
            <Button
              variant="outline"
              size="sm"
              onClick={handlePurgeDebugLog}
              disabled={purgeLoading || verifyLoading || testLoading}
              className="flex items-center gap-2"
            >
              {purgeLoading && <Loader2 className="h-4 w-4 animate-spin" />}
              Purge Logs
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={handleVerifyEventsQueue}
              disabled={purgeLoading || verifyLoading || testLoading}
              className="flex items-center gap-2"
            >
              {verifyLoading && <Loader2 className="h-4 w-4 animate-spin" />}
              Clear Queue
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={handleTestEvent}
              disabled={purgeLoading || verifyLoading || testLoading}
              className="flex items-center gap-2"
            >
              {testLoading && <Loader2 className="h-4 w-4 animate-spin" />}
              Test Event
            </Button>
          </div>
        )}


          <div className="flex items-center justify-between">
            <Label htmlFor="enable-mail-logging" className="flex flex-col space-y-1">
              <span>Mail Logging</span>
              <span className="font-normal text-sm text-muted-foreground">
                                Track and log email activity
                            </span>
            </Label>
            <Switch
              id="enable-mail-logging"
              checked={settings.bento_enable_mail_logging === '1'}
              onCheckedChange={(checked) => onUpdate('bento_enable_mail_logging', checked ? '1' : '0')}
            />
          </div>
       
      </CardContent>
    </Card>
  );
}
