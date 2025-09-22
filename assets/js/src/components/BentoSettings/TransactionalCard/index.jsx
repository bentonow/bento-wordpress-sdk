import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardContent, CardTitle, CardDescription } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Loader2, AlertCircle, MailWarning, RotateCcw } from 'lucide-react';
import { callBentoApi, getConnectionStatus } from '@/lib/connection-util';

export function TransactionalCard({ settings, onUpdate }) {
  const [loading, setLoading] = useState(false);
  const [authors, setAuthors] = useState([]);
  const [error, setError] = useState(null);
  const connectionStatus = getConnectionStatus(settings);

  useEffect(() => {
    if (settings.bento_enable_transactional === '1' && connectionStatus.connected) {
      fetchAuthors();
    }
  }, [settings.bento_enable_transactional, connectionStatus.connected]);

  const fetchAuthors = async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await callBentoApi('bento_fetch_authors');
      if (result.data?.data?.length) {
        setAuthors(result.data.data);
      } else {
        setError('No authors available');
      }
    } catch (error) {
      setError('Failed to fetch authors');
    } finally {
      setLoading(false);
    }
  };

  const renderAuthorSelect = () => {
    if (!connectionStatus.connected) {
      return (
        <Alert>
          <AlertCircle className="h-4 w-4" />
          <AlertDescription className="ml-2">
            Please verify your connection settings first.
          </AlertDescription>
        </Alert>
      );
    }

    if (loading) {
      return (
        <div className="flex items-center space-x-2">
          <Loader2 className="h-4 w-4 animate-spin" />
          <span>Loading authors...</span>
        </div>
      );
    }

    if (error) {
      return (
        <Alert variant="destructive">
          <AlertCircle className="h-6 w-6" />
          <AlertDescription className="ml-2 pt-2">
            {error}
          </AlertDescription>
        </Alert>
      );
    }

    return (
      <div className="flex items-center space-x-2">
        <Select
          value={settings.bento_from_email}
          onValueChange={(value) => onUpdate('bento_from_email', value)}
        >
          <SelectTrigger className="w-full">
            <SelectValue placeholder="Select a sender" />
          </SelectTrigger>
          <SelectContent>
            {authors.map((author) => (
              <SelectItem
                key={author.id}
                value={author.attributes.email}
              >
                {author.attributes.name} ({author.attributes.email})
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Button
          type="button"
          variant="outline"
          size="icon"
          onClick={fetchAuthors}
          disabled={loading}
          aria-label="Refresh Bento authors"
        >
          {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <RotateCcw className="h-4 w-4" />}
        </Button>
      </div>
    );
  };

  return (
    <Card className="mb-6 rounded-md break-inside-avoid-column">
      <CardHeader>
        <CardTitle>Transactional Email Settings</CardTitle>
        <CardDescription>
          Configure how Bento handles transactional emails
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        <div className="flex items-center justify-between">
          <Label htmlFor="enable-transactional" className="flex flex-col space-y-1">
            <span>Enable Transactional Emails</span>
            <span className="font-normal text-sm text-muted-foreground">
              Send transactional emails via Bento
            </span>
          </Label>
          <Switch
            id="enable-transactional"
            checked={settings.bento_enable_transactional === '1'}
            onCheckedChange={(checked) => onUpdate('bento_enable_transactional', checked ? '1' : '0')}
          />
        </div>

        {settings.bento_enable_transactional === '1' && (
          <>
            <div className="space-y-2">
              <Label htmlFor="from-email">From Email <span className={'text-zinc-400 text-xs'}>(bento author)</span></Label>
              {renderAuthorSelect()}
            </div>

            <div className="flex items-center justify-between">
              <Label htmlFor="override" className="flex flex-col space-y-1">
                <span>Transactional Override</span>
                <span className="font-normal text-sm text-muted-foreground">
                  Send emails to all users including unsubscribes
                </span>
              </Label>
              <Switch
                id="override"
                checked={settings.bento_transactional_override === '1'}
                onCheckedChange={(checked) => onUpdate('bento_transactional_override', checked ? '1' : '0')}
              />
            </div>

            <div className="flex items-center justify-between">
              <Label htmlFor="reply-to" className="flex flex-col space-y-1">
                <span>Include Reply-To Header</span>
                <span className="font-normal text-sm text-muted-foreground">
                  Pass any Reply-To value from WordPress to Bento
                </span>
              </Label>
              <Switch
                id="reply-to"
                checked={settings.bento_enable_reply_to !== '0'}
                onCheckedChange={(checked) => onUpdate('bento_enable_reply_to', checked ? '1' : '0')}
              />
            </div>

            <Alert className="flex flex-row items-center gap-2">
              <MailWarning className="h-8 w-8 pt-0 pr-2 -mt-2 stroke-zinc-500" />
              <AlertDescription className="ml-2">
                Bento Transactional Email is designed for low volume transactional emails.
                It is not designed for high volume/frequent bulk sending.
              </AlertDescription>
            </Alert>
          </>
        )}
      </CardContent>
    </Card>
  );
}
