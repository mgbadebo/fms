import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Tag, Plus } from 'lucide-react';

export default function LabelTemplates() {
    const [templates, setTemplates] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchTemplates();
    }, []);

    const fetchTemplates = async () => {
        try {
            const response = await api.get('/api/v1/label-templates');
            setTemplates(response.data.data || response.data);
        } catch (error) {
            console.error('Error fetching templates:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }

    return (
        <div>
            <div className="flex justify-between items-center mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Label Templates</h1>
                    <p className="mt-2 text-gray-600">Manage your label printing templates</p>
                </div>
            </div>

            {templates.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Tag className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No label templates yet</h3>
                    <p className="text-gray-500">
                        Label templates are created through the API. Use the default template for now.
                    </p>
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {templates.map((template) => (
                        <div key={template.id} className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-start justify-between mb-4">
                                <div className="flex items-center">
                                    <div className="bg-orange-100 p-2 rounded-lg">
                                        <Tag className="h-6 w-6 text-orange-600" />
                                    </div>
                                    <h3 className="ml-3 text-lg font-semibold text-gray-900">
                                        {template.name}
                                    </h3>
                                </div>
                                {template.is_default && (
                                    <span className="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">
                                        Default
                                    </span>
                                )}
                            </div>
                            <div className="space-y-2">
                                <div>
                                    <p className="text-xs text-gray-500">Code</p>
                                    <p className="text-sm font-medium text-gray-900">
                                        {template.code}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Target Type</p>
                                    <p className="text-sm text-gray-900">
                                        {template.target_type?.split('\\').pop() || 'N/A'}
                                    </p>
                                </div>
                                {template.template_body && (
                                    <div>
                                        <p className="text-xs text-gray-500">Template Preview</p>
                                        <pre className="text-xs bg-gray-50 p-2 rounded mt-1 overflow-auto max-h-32">
                                            {template.template_body.substring(0, 200)}...
                                        </pre>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

