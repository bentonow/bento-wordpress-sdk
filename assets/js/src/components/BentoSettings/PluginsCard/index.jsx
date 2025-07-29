import React from 'react';
import { Card, CardHeader, CardContent, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { AlertCircle, CheckCircle2, CircleDashed } from 'lucide-react';

export function PluginsCard() {
  const { plugins, versions } = window.bentoAdmin || {};
  const pluginsList = [
    { id: 'WooCommerce', name: 'WooCommerce', version: versions?.WooCommerce },
    { id: 'LEARNDASH_VERSION', name: 'LearnDash', version: versions?.LEARNDASH_VERSION },
    { id: 'SureCart', name: 'SureCart', version: versions?.SureCart },
    { id: 'WPForms', name: 'WPForms', version: versions?.WPForms },
    { id: 'Easy_Digital_Downloads', name: 'Easy Digital Downloads', version: versions?.Easy_Digital_Downloads },
    { id: 'ELEMENTOR_VERSION', name: 'Elementor', version: versions?.Elementor },
    { id: 'BRICKS_VERSION', name: 'Bricks Builder', version: versions?.Bricks },
    { id: 'TVE_IN_ARCHITECT', name: 'Thrive Themes', version: versions?.Thrive }
  ];

  return (
    <Card className="mb-6 rounded-md break-inside-avoid-column">
      <CardHeader>
        <CardTitle>Supported Plugins</CardTitle>
        <CardDescription>
          Status of supported plugin integrations
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {pluginsList.map((plugin) => (
            <div key={plugin.id} className={`flex items-center justify-between p-2 rounded ${plugins?.[plugin.id] ? 'bg-green-100/80' : 'bg-muted'}`}>
              <div className="flex items-center gap-2">
                {plugins?.[plugin.id] ? (
                  <CheckCircle2 className="h-4 w-4 text-green-600" />
                ) : (
                  <CircleDashed className="h-4 w-4 text-gray-400" />
                )}
                <span className={`${plugins?.[plugin.id] ? 'text-green-900' : ''}`}>
                  {plugin.name}
                </span>
              </div>
              {plugins?.[plugin.id] && plugin.version && (
                <Badge variant="outline"
                className={`${plugins?.[plugin.id] ? 'text-green-600 border-green-300 bg-green-50' : 'text-gray-400'}`}
                >v{plugin.version}</Badge>
              )}
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}