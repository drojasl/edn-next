export function useDebounce(delay: number = 1000) {
    const createDebouncer = <T>(apiCall: (items: T[]) => Promise<void>) => {
        let buffer: T[] = []
        let timeout: any = null

        return (item: T, uniqueKey: keyof T) => {
            const index = buffer.findIndex((b: any) => b[uniqueKey] === (item as any)[uniqueKey])
            if (index !== -1) buffer[index] = item
            else buffer.push(item)

            if (timeout) clearTimeout(timeout)
            timeout = setTimeout(async () => {
                const payload = [...buffer]
                buffer = []
                await apiCall(payload)
            }, delay)
        }
    }

    const createStateDebouncer = <T>(apiCall: (state: T) => Promise<void>) => {
        let latestState: T | null = null
        let timeout: any = null

        return (state: T) => {
            latestState = state
            if (timeout) clearTimeout(timeout)
            timeout = setTimeout(async () => {
                if (latestState) {
                    const payload = latestState
                    latestState = null
                    await apiCall(payload)
                }
            }, delay)
        }
    }

    return { createDebouncer, createStateDebouncer }
}
