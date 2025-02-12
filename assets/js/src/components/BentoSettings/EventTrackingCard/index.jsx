import React from 'react';
import { Card, CardHeader, CardContent, CardTitle, CardDescription } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function EventTrackingCard({ settings, onUpdate }) {
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