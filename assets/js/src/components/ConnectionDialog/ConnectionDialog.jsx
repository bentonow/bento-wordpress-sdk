import { useState } from 'react';
import { useToast } from '@/hooks/use-toast';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Loader2 } from 'lucide-react';
import { callBentoApi } from '@/lib/connection-util';
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogFooter,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogAction,
  AlertDialogCancel,
} from '@/components/ui/alert-dialog';

export function ConnectionDialog({ onConnected, onDismiss }) {
  const { toast } = useToast();
  const [validating, setValidating] = useState(false);
  const [credentials, setCredentials] = useState({
    bento_site_key: '',
    bento_publishable_key: '',
    bento_secret_key: ''
  });

  const handleInputChange = (key) => (e) => {
    setCredentials(prev => ({ ...prev, [key]: e.target.value }));
  };

  const handleValidate = async () => {
    setValidating(true);
    try {
      const result = await callBentoApi('bento_validate_connection', {
        site_key: credentials.bento_site_key,
        publishable_key: credentials.bento_publishable_key,
        secret_key: credentials.bento_secret_key
      });

      const status = result.connection_status;

      if (status.connected) {
        toast({
          title: "Connection Successful",
          description: "Your Bento credentials have been validated and saved."
        });
        onConnected();
      } else {
        toast({
          title: "Connection Failed",
          description: `${status.message} (${status.code})`,
          variant: "destructive"
        });
        setValidating(false);
      }
    } catch (error) {
      toast({
        title: "Connection Error",
        description: "Failed to validate credentials. Please try again.",
        variant: "destructive"
      });
      setValidating(false);
    }
  };

  return (
    <AlertDialog open={true}>
      <AlertDialogContent className="sm:max-w-[500px]">
        <AlertDialogHeader>
          <AlertDialogTitle>Welcome To Bento!</AlertDialogTitle>
          <AlertDialogDescription>
            <div className="flex flex-row">
              <div>
                Enter your Bento API credentials to get started.
                You can find these in your
                <a
                  href="https://app.bentonow.com/account/teams"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="pl-1 text-primary hover:underline"
                >
                  Bento dashboard
                </a>.
              </div>
              <div>
                <img
                  src={`${window.bentoAdmin?.pluginUrl || ''}/assets/img/no-messages.webp`}
                  alt="Bento Connection Required"
                  className="max-w-[150px] h-auto -mt-12"
                />
              </div>
            </div>
          </AlertDialogDescription>
        </AlertDialogHeader>
        <div className="space-y-4 py-4">
          <div className="space-y-2">
            <Label htmlFor="site-key">Site Key</Label>
            <Input
              id="site-key"
              value={credentials.bento_site_key}
              onChange={handleInputChange('bento_site_key')}
              placeholder="Enter your Site Key"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="publishable-key">Publishable Key</Label>
            <Input
              id="publishable-key"
              value={credentials.bento_publishable_key}
              onChange={handleInputChange('bento_publishable_key')}
              placeholder="Enter your Publishable Key"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="secret-key">Secret Key</Label>
            <Input
              id="secret-key"
              type="password"
              value={credentials.bento_secret_key}
              onChange={handleInputChange('bento_secret_key')}
              placeholder="Enter your Secret Key"
            />
          </div>
        </div>
        <AlertDialogFooter>
          <AlertDialogCancel onClick={onDismiss}>Configure Later</AlertDialogCancel>
          <AlertDialogAction onClick={handleValidate} disabled={validating}>
            {validating && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {validating ? 'Validating...' : 'Connect to Bento'}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}