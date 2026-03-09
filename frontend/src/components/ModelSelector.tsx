import React from 'react';
import type { ModelType } from '../types';

interface ModelSelectorProps {
  selectedModel: ModelType;
  onModelChange: (model: ModelType) => void;
}

const MODEL_CONFIG = {
  grok: { name: 'Grok', apiModel: 'grok-beta' },
  gemini: { name: 'Gemini', apiModel: 'google/gemini-pro' },
  gpt: { name: 'GPT-4', apiModel: 'openai/gpt-4' },
};

export const ModelSelector: React.FC<ModelSelectorProps> = ({
  selectedModel,
  onModelChange,
}) => {
  return (
    <div className="flex gap-2 p-4 bg-gray-100 border-b">
      <span className="text-sm text-gray-600 self-center">选择模型：</span>
      {(Object.keys(MODEL_CONFIG) as ModelType[]).map((model) => (
        <button
          key={model}
          onClick={() => onModelChange(model)}
          className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
            selectedModel === model
              ? 'bg-blue-500 text-white'
              : 'bg-white text-gray-700 hover:bg-gray-200'
          }`}
        >
          {MODEL_CONFIG[model].name}
        </button>
      ))}
    </div>
  );
};

export const getApiModel = (model: ModelType): string => {
  return MODEL_CONFIG[model].apiModel;
};
