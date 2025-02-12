import React, { useState } from 'react';
import { useToast } from '@/hooks/use-toast';
import { Card, CardHeader, CardContent, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Loader2, ChevronUp, ChevronDown, ThumbsUp, Frown } from 'lucide-react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { callBentoApi, getConnectionStatus, getBadgeVariant } from '@/lib/connection-util';

export function ConnectionCard({ settings, onUpdate }) {
  const { toast } = useToast();
  const [validating, setValidating] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const [localSettings, setLocalSettings] = useState({
    bento_site_key: settings.bento_site_key || '',
    bento_publishable_key: settings.bento_publishable_key || '',
    bento_secret_key: settings.bento_secret_key || ''
  });

  const handleInputChange = (key) => (e) => {
    setLocalSettings(prev => ({ ...prev, [key]: e.target.value }));
  };

  const connectionStatus = getConnectionStatus(settings);

  const handleSave = async () => {
    setValidating(true);
    try {
      const result = await callBentoApi('bento_validate_connection', {
        site_key: localSettings.bento_site_key,
        publishable_key: localSettings.bento_publishable_key,
        secret_key: localSettings.bento_secret_key
      });

      const status = result.connection_status;

      // Update all settings including connection status
      await Promise.all([
        ...Object.entries(localSettings).map(([key, value]) => onUpdate(key, value)),
        onUpdate('bento_connection_status', JSON.stringify(status))
      ]);

      toast({
        title: status.connected ? "Connection Successful" : "Connection Failed",
        description: `${status.message} (${status.code})`,
        variant: status.connected ? "default" : "destructive"
      });
    } catch (error) {
      const errorStatus = {
        connected: false,
        message: 'Service error',
        code: 500,
        timestamp: Date.now()
      };

      await onUpdate('bento_connection_status', JSON.stringify(errorStatus));

      toast({
        title: "Connection Error",
        description: "Failed to validate credentials. Please try again.",
        variant: "destructive"
      });
    } finally {
      setValidating(false);
    }
  };

  return (
    <Card className="mb-6 rounded-md break-inside-avoid-column">
      <Collapsible open={isOpen} onOpenChange={setIsOpen}>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Connection Settings</CardTitle>
            <Badge
              variant="outline"
              className={connectionStatus.code === 200 ? "text-green-600 border-green-300 bg-green-50" : "text-rose-600 border-rose-300 bg-rose-50"}
            >
              {connectionStatus.code === 200 ? (
                <ThumbsUp className="h-4 w-4 mr-2" />
              ) : (
                <Frown className="h-4 w-4 mr-2" />
              )}
              {connectionStatus.message}
              {connectionStatus.code !== 200 && ` (${connectionStatus.code})`}
            </Badge>
          </div>
          <div className="flex items-center justify-between">
            <CardDescription>
              Configure your Bento API credentials
            </CardDescription>
            <CollapsibleTrigger className="rounded-full hover:bg-muted p-2">
              {isOpen ? (
                <ChevronUp className="h-4 w-4" />
              ) : (
                <ChevronDown className="h-4 w-4" />
              )}
            </CollapsibleTrigger>
          </div>
        </CardHeader>
        <CollapsibleContent>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="site-key">Site Key</Label>
              <Input
                id="site-key"
                value={localSettings.bento_site_key}
                onChange={handleInputChange('bento_site_key')}
                placeholder="Enter your Site Key"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="publishable-key">Publishable Key</Label>
              <Input
                id="publishable-key"
                value={localSettings.bento_publishable_key}
                onChange={handleInputChange('bento_publishable_key')}
                placeholder="Enter your Publishable Key"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="secret-key">Secret Key</Label>
              <Input
                id="secret-key"
                type="password"
                value={localSettings.bento_secret_key}
                onChange={handleInputChange('bento_secret_key')}
                placeholder="Enter your Secret Key"
              />
            </div>
          </CardContent>
          <CardFooter>
            <Button onClick={handleSave} disabled={validating}>
              {validating && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {validating ? 'Validating...' : 'Save & Validate Connection'}
            </Button>
          </CardFooter>
        </CollapsibleContent>
      </Collapsible>
    </Card>
  );
}