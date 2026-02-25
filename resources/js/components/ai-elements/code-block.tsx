'use client';

import { CheckIcon, CopyIcon } from 'lucide-react';
import type { ComponentProps, CSSProperties, HTMLAttributes } from 'react';
import { createContext, memo, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import type { HighlighterGeneric, ThemedToken } from 'shiki/core';
import { createHighlighterCore } from 'shiki/core';
import { createOnigurumaEngine } from 'shiki/engine/oniguruma';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';

// Shiki uses bitflags for font styles: 1=italic, 2=bold, 4=underline
// biome-ignore lint/suspicious/noBitwiseOperators: shiki bitflag check

const isItalic = (fontStyle: number | undefined) => fontStyle && fontStyle & 1;
// biome-ignore lint/suspicious/noBitwiseOperators: shiki bitflag check

// oxlint-disable-next-line eslint(no-bitwise)
const isBold = (fontStyle: number | undefined) => fontStyle && fontStyle & 2;
const isUnderline = (fontStyle: number | undefined) =>
    // biome-ignore lint/suspicious/noBitwiseOperators: shiki bitflag check
    // oxlint-disable-next-line eslint(no-bitwise)
    fontStyle && fontStyle & 4;

// Transform tokens to include pre-computed keys to avoid noArrayIndexKey lint
interface KeyedToken {
    token: ThemedToken;
    key: string;
}
interface KeyedLine {
    tokens: KeyedToken[];
    key: string;
}

const addKeysToTokens = (lines: ThemedToken[][]): KeyedLine[] =>
    lines.map((line, lineIdx) => ({
        key: `line-${lineIdx}`,
        tokens: line.map((token, tokenIdx) => ({
            key: `line-${lineIdx}-${tokenIdx}`,
            token,
        })),
    }));

// Token rendering component
const TokenSpan = ({ token }: { token: ThemedToken }) => (
    <span
        className="dark:bg-(--shiki-dark-bg)! dark:text-(--shiki-dark)!"
        style={
            {
                backgroundColor: token.bgColor,
                color: token.color,
                fontStyle: isItalic(token.fontStyle) ? 'italic' : undefined,
                fontWeight: isBold(token.fontStyle) ? 'bold' : undefined,
                textDecoration: isUnderline(token.fontStyle) ? 'underline' : undefined,
                ...token.htmlStyle,
            } as CSSProperties
        }
    >
        {token.content}
    </span>
);

// Line rendering component
const LineSpan = ({ keyedLine, showLineNumbers }: { keyedLine: KeyedLine; showLineNumbers: boolean }) => <span className={showLineNumbers ? LINE_NUMBER_CLASSES : 'block'}>{keyedLine.tokens.length === 0 ? '\n' : keyedLine.tokens.map(({ token, key }) => <TokenSpan key={key} token={token} />)}</span>;

// Types
type CodeBlockProps = HTMLAttributes<HTMLDivElement> & {
    code: string;
    language: string;
    showLineNumbers?: boolean;
};

interface TokenizedCode {
    tokens: ThemedToken[][];
    fg: string;
    bg: string;
}

interface CodeBlockContextType {
    code: string;
}

// Context
const CodeBlockContext = createContext<CodeBlockContextType>({
    code: '',
});

// Map of supported languages to their dynamic imports.
// Plaintext/text are handled as fallback (no grammar needed).
const LANGUAGE_IMPORTS: Record<string, () => Promise<unknown>> = {
    php: () => import('shiki/langs/php'),
    javascript: () => import('shiki/langs/javascript'),
    js: () => import('shiki/langs/javascript'),
    typescript: () => import('shiki/langs/typescript'),
    ts: () => import('shiki/langs/typescript'),
    json: () => import('shiki/langs/json'),
    bash: () => import('shiki/langs/bash'),
    shell: () => import('shiki/langs/shell'),
    sh: () => import('shiki/langs/bash'),
    sql: () => import('shiki/langs/sql'),
    html: () => import('shiki/langs/html'),
    css: () => import('shiki/langs/css'),
    yaml: () => import('shiki/langs/yaml'),
    yml: () => import('shiki/langs/yaml'),
    markdown: () => import('shiki/langs/markdown'),
    md: () => import('shiki/langs/markdown'),
    diff: () => import('shiki/langs/diff'),
    xml: () => import('shiki/langs/xml'),
    python: () => import('shiki/langs/python'),
    py: () => import('shiki/langs/python'),
};

// Theme imports (only 2 themes)
const THEME_IMPORTS = [import('shiki/themes/github-light'), import('shiki/themes/github-dark')];

// Highlighter cache (singleton per language)
const highlighterCache = new Map<string, Promise<HighlighterGeneric<string, string>>>();

// Token cache
const tokensCache = new Map<string, TokenizedCode>();

// Subscribers for async token updates
const subscribers = new Map<string, Set<(result: TokenizedCode) => void>>();

const getTokensCacheKey = (code: string, language: string) => {
    const start = code.slice(0, 100);
    const end = code.length > 100 ? code.slice(-100) : '';
    return `${language}:${code.length}:${start}:${end}`;
};

const getHighlighter = (language: string): Promise<HighlighterGeneric<string, string>> | null => {
    const langKey = language.toLowerCase();

    const cached = highlighterCache.get(langKey);
    if (cached) {
        return cached;
    }

    const langImport = LANGUAGE_IMPORTS[langKey];
    if (!langImport) {
        // Unsupported language — return null, component will show plain text
        return null;
    }

    const highlighterPromise = createHighlighterCore({
        langs: [langImport()],
        themes: THEME_IMPORTS,
        engine: createOnigurumaEngine(import('shiki/wasm')),
    });

    highlighterCache.set(langKey, highlighterPromise);
    return highlighterPromise;
};

// Create raw tokens for immediate display while highlighting loads
const createRawTokens = (code: string): TokenizedCode => ({
    bg: 'transparent',
    fg: 'inherit',
    tokens: code.split('\n').map((line) =>
        line === ''
            ? []
            : [
                  {
                      color: 'inherit',
                      content: line,
                  } as ThemedToken,
              ],
    ),
});

// Synchronous highlight with callback for async results
export const highlightCode = (
    code: string,
    language: string,
    // oxlint-disable-next-line eslint-plugin-promise(prefer-await-to-callbacks)
    callback?: (result: TokenizedCode) => void,
): TokenizedCode | null => {
    const tokensCacheKey = getTokensCacheKey(code, language);

    // Return cached result if available
    const cached = tokensCache.get(tokensCacheKey);
    if (cached) {
        return cached;
    }

    // Unsupported language — no highlighting available
    const highlighterPromise = getHighlighter(language);
    if (!highlighterPromise) {
        return null;
    }

    // Subscribe callback if provided
    if (callback) {
        if (!subscribers.has(tokensCacheKey)) {
            subscribers.set(tokensCacheKey, new Set());
        }
        subscribers.get(tokensCacheKey)?.add(callback);
    }

    // Start highlighting in background - fire-and-forget async pattern
    highlighterPromise
        // oxlint-disable-next-line eslint-plugin-promise(prefer-await-to-then)
        .then((highlighter) => {
            const availableLangs = highlighter.getLoadedLanguages();
            const langToUse = availableLangs.includes(language) ? language : 'text';

            const result = highlighter.codeToTokens(code, {
                lang: langToUse,
                themes: {
                    dark: 'github-dark',
                    light: 'github-light',
                },
            });

            const tokenized: TokenizedCode = {
                bg: result.bg ?? 'transparent',
                fg: result.fg ?? 'inherit',
                tokens: result.tokens,
            };

            // Cache the result
            tokensCache.set(tokensCacheKey, tokenized);

            // Notify all subscribers
            const subs = subscribers.get(tokensCacheKey);
            if (subs) {
                for (const sub of subs) {
                    sub(tokenized);
                }
                subscribers.delete(tokensCacheKey);
            }
        })
        // oxlint-disable-next-line eslint-plugin-promise(prefer-await-to-then), eslint-plugin-promise(prefer-await-to-callbacks)
        .catch((error) => {
            console.error('Failed to highlight code:', error);
            subscribers.delete(tokensCacheKey);
        });

    return null;
};

// Line number styles using CSS counters
const LINE_NUMBER_CLASSES = cn('block', 'before:content-[counter(line)]', 'before:inline-block', 'before:[counter-increment:line]', 'before:w-8', 'before:mr-4', 'before:text-right', 'before:text-muted-foreground/50', 'before:font-mono', 'before:select-none');

const CodeBlockBody = memo(
    ({ tokenized, showLineNumbers, className }: { tokenized: TokenizedCode; showLineNumbers: boolean; className?: string }) => {
        const preStyle = useMemo(
            () => ({
                backgroundColor: tokenized.bg,
                color: tokenized.fg,
            }),
            [tokenized.bg, tokenized.fg],
        );

        const keyedLines = useMemo(() => addKeysToTokens(tokenized.tokens), [tokenized.tokens]);

        return (
            <pre className={cn('m-0 p-4 text-sm dark:bg-(--shiki-dark-bg)! dark:text-(--shiki-dark)!', className)} style={preStyle}>
                <code className={cn('font-mono text-sm', showLineNumbers && '[counter-increment:line_0] [counter-reset:line]')}>
                    {keyedLines.map((keyedLine) => (
                        <LineSpan key={keyedLine.key} keyedLine={keyedLine} showLineNumbers={showLineNumbers} />
                    ))}
                </code>
            </pre>
        );
    },
    (prevProps, nextProps) => prevProps.tokenized === nextProps.tokenized && prevProps.showLineNumbers === nextProps.showLineNumbers && prevProps.className === nextProps.className,
);

CodeBlockBody.displayName = 'CodeBlockBody';

export const CodeBlockContainer = ({ className, language, style, ...props }: HTMLAttributes<HTMLDivElement> & { language: string }) => (
    <div
        className={cn('group relative w-full overflow-hidden rounded-md border bg-background text-foreground', className)}
        data-language={language}
        style={{
            containIntrinsicSize: 'auto 200px',
            contentVisibility: 'auto',
            ...style,
        }}
        {...props}
    />
);

export const CodeBlockHeader = ({ children, className, ...props }: HTMLAttributes<HTMLDivElement>) => (
    <div className={cn('flex items-center justify-between border-b bg-muted/80 px-3 py-2 text-xs text-muted-foreground', className)} {...props}>
        {children}
    </div>
);

export const CodeBlockTitle = ({ children, className, ...props }: HTMLAttributes<HTMLDivElement>) => (
    <div className={cn('flex items-center gap-2', className)} {...props}>
        {children}
    </div>
);

export const CodeBlockFilename = ({ children, className, ...props }: HTMLAttributes<HTMLSpanElement>) => (
    <span className={cn('font-mono', className)} {...props}>
        {children}
    </span>
);

export const CodeBlockActions = ({ children, className, ...props }: HTMLAttributes<HTMLDivElement>) => (
    <div className={cn('-my-1 -mr-1 flex items-center gap-2', className)} {...props}>
        {children}
    </div>
);

export const CodeBlockContent = ({ code, language, showLineNumbers = false }: { code: string; language: string; showLineNumbers?: boolean }) => {
    // Memoized raw tokens for immediate display
    const rawTokens = useMemo(() => createRawTokens(code), [code]);

    // Synchronous cached/raw result — updates immediately when inputs change
    const syncResult = useMemo(() => highlightCode(code, language) ?? rawTokens, [code, language, rawTokens]);

    // Async highlighted result, tracked with a key to discard stale results
    const [asyncState, setAsyncState] = useState<{ key: string; result: TokenizedCode } | null>(null);

    useEffect(() => {
        let cancelled = false;
        const key = `${code}|${language}`;

        // Subscribe to async highlighting result
        highlightCode(code, language, (result) => {
            if (!cancelled) {
                setAsyncState({ key, result });
            }
        });

        return () => {
            cancelled = true;
        };
    }, [code, language]);

    // Use async result only if it matches current inputs, otherwise fall back to sync
    const currentKey = `${code}|${language}`;
    const tokenized = asyncState?.key === currentKey ? asyncState.result : syncResult;

    return (
        <div className="relative overflow-auto">
            <CodeBlockBody showLineNumbers={showLineNumbers} tokenized={tokenized} />
        </div>
    );
};

export const CodeBlock = ({ code, language, showLineNumbers = false, className, children, ...props }: CodeBlockProps) => {
    const contextValue = useMemo(() => ({ code }), [code]);

    return (
        <CodeBlockContext.Provider value={contextValue}>
            <CodeBlockContainer className={className} language={language} {...props}>
                {children}
                <CodeBlockContent code={code} language={language} showLineNumbers={showLineNumbers} />
            </CodeBlockContainer>
        </CodeBlockContext.Provider>
    );
};

export type CodeBlockCopyButtonProps = ComponentProps<typeof Button> & {
    onCopy?: () => void;
    onError?: (error: Error) => void;
    timeout?: number;
};

export const CodeBlockCopyButton = ({ onCopy, onError, timeout = 2000, children, className, ...props }: CodeBlockCopyButtonProps) => {
    const [isCopied, setIsCopied] = useState(false);
    const timeoutRef = useRef<number>(0);
    const { code } = useContext(CodeBlockContext);

    const copyToClipboard = useCallback(async () => {
        if (typeof window === 'undefined' || !navigator?.clipboard?.writeText) {
            onError?.(new Error('Clipboard API not available'));
            return;
        }

        try {
            if (!isCopied) {
                await navigator.clipboard.writeText(code);
                setIsCopied(true);
                onCopy?.();
                timeoutRef.current = window.setTimeout(() => setIsCopied(false), timeout);
            }
        } catch (error) {
            onError?.(error as Error);
        }
    }, [code, onCopy, onError, timeout, isCopied]);

    useEffect(
        () => () => {
            window.clearTimeout(timeoutRef.current);
        },
        [],
    );

    const Icon = isCopied ? CheckIcon : CopyIcon;

    return (
        <Button className={cn('shrink-0', className)} onClick={copyToClipboard} size="icon" variant="ghost" {...props}>
            {children ?? <Icon size={14} />}
        </Button>
    );
};

export type CodeBlockLanguageSelectorProps = ComponentProps<typeof Select>;

export const CodeBlockLanguageSelector = (props: CodeBlockLanguageSelectorProps) => <Select {...props} />;

export type CodeBlockLanguageSelectorTriggerProps = ComponentProps<typeof SelectTrigger>;

export const CodeBlockLanguageSelectorTrigger = ({ className, ...props }: CodeBlockLanguageSelectorTriggerProps) => <SelectTrigger className={cn('h-7 border-none bg-transparent px-2 text-xs shadow-none', className)} size="sm" {...props} />;

export type CodeBlockLanguageSelectorValueProps = ComponentProps<typeof SelectValue>;

export const CodeBlockLanguageSelectorValue = (props: CodeBlockLanguageSelectorValueProps) => <SelectValue {...props} />;

export type CodeBlockLanguageSelectorContentProps = ComponentProps<typeof SelectContent>;

export const CodeBlockLanguageSelectorContent = ({ align = 'end', ...props }: CodeBlockLanguageSelectorContentProps) => <SelectContent align={align} {...props} />;

export type CodeBlockLanguageSelectorItemProps = ComponentProps<typeof SelectItem>;

export const CodeBlockLanguageSelectorItem = (props: CodeBlockLanguageSelectorItemProps) => <SelectItem {...props} />;
